"""Build a checked extension ZIP from explicitly allowed tracked sources."""

import hashlib
import os
from pathlib import Path, PurePosixPath
import re
import subprocess
import xml.etree.ElementTree as ET
import zipfile


MODULES = ("br-isms", "br-isms-catalog")
ROOT_FILES = {"extension.xml", "README.md", "LICENSE"}
FOLDERS = {"src", "dictionaries", "images", "data", "addon"}
SUFFIXES = {
    ".php", ".xml", ".svg", ".png", ".jpg", ".jpeg", ".gif", ".webp",
    ".ico", ".css", ".js", ".json", ".csv", ".xlsx", ".txt",
    ".woff", ".woff2", ".ttf", ".eot",
}
EXCLUDED = {"tests", "node_modules", "vendor", "build", "dist"}


def included(name):
    path = PurePosixPath(name)
    if path.is_absolute() or ".." in path.parts:
        raise ValueError(f"Unsafe package path: {name}")
    if any(part.startswith(".") or part in EXCLUDED for part in path.parts):
        return False
    if name in ROOT_FILES:
        return True
    if len(path.parts) < 2 or path.parts[0] not in MODULES:
        return False
    module = path.parts[0]
    if len(path.parts) == 2:
        return path.name in {f"module.{module}.php", f"datamodel.{module}.xml"}
    return path.parts[1] in FOLDERS and path.suffix.lower() in SUFFIXES


def build(root, tracked, revision):
    root = Path(root).resolve()
    if not re.fullmatch(r"[0-9a-f]{40}", revision):
        raise ValueError("Expected a full Git commit SHA")
    names = sorted({name for name in tracked if included(name)})
    required = set(ROOT_FILES)
    for module in MODULES:
        required.update({
            f"{module}/module.{module}.php",
            f"{module}/datamodel.{module}.xml",
            f"{module}/dictionaries/en.dict.{module}.php",
            f"{module}/dictionaries/de.dict.{module}.php",
        })
    missing = required.difference(names)
    if missing:
        raise ValueError(f"Missing required package files: {sorted(missing)}")
    for name in names:
        source = root / name
        if source.is_symlink() or not source.is_file() or not source.resolve().is_relative_to(root):
            raise ValueError(f"Invalid package source: {name}")
    descriptor = ET.parse(root / "extension.xml").getroot()
    code = (descriptor.findtext("extension_code") or "").strip()
    version = (descriptor.findtext("version") or "").strip()
    if code != "br-isms" or not re.fullmatch(r"[0-9]+(?:\.[0-9]+){2}(?:[-+][A-Za-z0-9.-]+)?", version):
        raise ValueError("Invalid extension identity or version")
    directory = root / ".tmp/packages"
    directory.mkdir(parents=True, exist_ok=True)
    archive = directory / f"{code}-{version}-{revision[:12]}.zip"
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED) as package:
        for name in names:
            entry = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            package.writestr(entry, (root / name).read_bytes())
    with zipfile.ZipFile(archive) as package:
        if package.namelist() != names or package.testzip() is not None:
            raise ValueError("ZIP integrity or inventory verification failed")
        for name in names:
            if package.read(name) != (root / name).read_bytes():
                raise ValueError(f"ZIP content mismatch: {name}")
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    print(f"Built {archive.name}: {len(names)} files; SHA-256 {digest}")
    return archive


def main():
    root = Path(__file__).resolve().parent.parent
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z"], cwd=root
    ).decode("utf-8").rstrip("\0").split("\0")
    revision = subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=root, text=True
    ).strip()
    archive = build(root, tracked, revision)
    if os.environ.get("GITHUB_OUTPUT"):
        with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as output:
            output.write(f"path={archive.relative_to(root).as_posix()}\n")


if __name__ == "__main__":
    main()
