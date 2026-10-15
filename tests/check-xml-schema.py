"""Validate extension and datamodel XML with xmllint and downloaded XSDs."""

from pathlib import Path
import os
import subprocess
import xml.etree.ElementTree as ET


ROOT = Path(__file__).resolve().parent.parent
EXCLUDED = {".git", ".tmp", "node_modules", "vendor", "build", "dist"}
SCHEMAS = {
    "itop_design": (
        "https://rudnerbjoern.github.io/iTop-schema/3.2/itop_design.xsd",
        ROOT / ".tmp/xsd-ci/itop_design.xsd",
    ),
    "extension": (
        "https://rudnerbjoern.github.io/iTop-schema/extension.xsd",
        ROOT / ".tmp/xsd-ci/extension.xsd",
    ),
}
XSI_LOCATION = "{http://www.w3.org/2001/XMLSchema-instance}noNamespaceSchemaLocation"


def xml_files():
    for directory, folders, files in os.walk(ROOT, followlinks=False):
        folders[:] = [
            name for name in folders
            if name not in EXCLUDED and not (Path(directory) / name).is_symlink()
        ]
        for name in files:
            path = Path(directory) / name
            if path.suffix == ".xml" and not path.is_symlink():
                yield path


def main():
    counts = dict.fromkeys(SCHEMAS, 0)
    for path in sorted(xml_files()):
        relative = path.relative_to(ROOT)
        element = ET.parse(path).getroot()
        if element.tag in {"Set", "itop_data"} and path.parent.name == "data":
            print(f"Syntax only (no sample-data XSD configured): {relative}", flush=True)
            continue
        if element.tag not in SCHEMAS:
            raise ValueError(f"No schema configured for {relative}: {element.tag}")
        url, schema = SCHEMAS[element.tag]
        if element.get(XSI_LOCATION) != url:
            raise ValueError(f"Unexpected or missing schema reference: {relative}")
        if not schema.is_file():
            raise FileNotFoundError(f"Download the published schema first: {schema}")
        print(f"Validating {relative}", flush=True)
        subprocess.run(
            ["xmllint", "--nonet", "--noout", "--schema", str(schema), str(path)],
            check=True,
        )
        counts[element.tag] += 1
    for kind, count in counts.items():
        if not count:
            raise ValueError(f"No {kind} documents validated")
        print(f"{kind}: {count} schema validations passed")


if __name__ == "__main__":
    main()
