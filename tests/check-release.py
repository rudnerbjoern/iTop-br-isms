"""Require a matching stable version tag on a commit already included in main."""

import os
from pathlib import Path
import re
import subprocess
import xml.etree.ElementTree as ET


def validate_tag(tag, version):
    if not re.fullmatch(r"v(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)", tag):
        raise ValueError("Official releases require a stable vMAJOR.MINOR.PATCH tag")
    if tag != f"v{version}":
        raise ValueError("Release tag must match the version in extension.xml")


def main():
    root = Path(__file__).resolve().parent.parent
    tag = os.environ.get("RELEASE_TAG", "")
    descriptor = ET.parse(root / "extension.xml").getroot()
    validate_tag(tag, (descriptor.findtext("version") or "").strip())
    subprocess.run(
        ["git", "merge-base", "--is-ancestor", "HEAD", "refs/remotes/origin/main"],
        cwd=root,
        check=True,
    )
    print(f"Release tag {tag} matches the descriptor and is included in main.")


if __name__ == "__main__":
    main()
