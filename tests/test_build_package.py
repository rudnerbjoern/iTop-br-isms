"""Focused regression tests for extension ZIP packaging."""

import importlib.util
from pathlib import Path
import sys
import tempfile
import unittest
import zipfile

sys.dont_write_bytecode = True
SPEC = importlib.util.spec_from_file_location(
    "package_builder", Path(__file__).with_name("build-package.py")
)
BUILDER = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(BUILDER)


class PackageTests(unittest.TestCase):
    def setUp(self):
        temporary = Path(__file__).resolve().parent.parent / ".tmp"
        temporary.mkdir(exist_ok=True)
        self.temporary = tempfile.TemporaryDirectory(dir=temporary, prefix="package-test-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.tracked = ["extension.xml", "README.md", "LICENSE"]
        for module in BUILDER.MODULES:
            self.tracked.extend([
                f"{module}/module.{module}.php",
                f"{module}/datamodel.{module}.xml",
                f"{module}/dictionaries/en.dict.{module}.php",
                f"{module}/dictionaries/de.dict.{module}.php",
                f"{module}/images/icon.svg",
            ])
        for name in self.tracked:
            path = self.root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(b"fixture\r\n")
        (self.root / "extension.xml").write_text(
            "<extension><extension_code>br-isms</extension_code>"
            "<version>3.2.6</version></extension>"
        )

    def build(self, tracked=None):
        return BUILDER.build(
            self.root, self.tracked if tracked is None else tracked, "a" * 40
        )

    def test_inventory_and_source_bytes(self):
        archive = self.build()
        self.assertEqual(archive.name, "br-isms-3.2.6-aaaaaaaaaaaa.zip")
        with zipfile.ZipFile(archive) as package:
            self.assertEqual(package.namelist(), sorted(self.tracked))
            for name in self.tracked:
                self.assertEqual(package.read(name), (self.root / name).read_bytes())
        before = archive.read_bytes()
        self.assertEqual(self.build().read_bytes(), before)

    def test_release_filename_omits_commit(self):
        development = self.build()
        release = BUILDER.build(self.root, self.tracked, "a" * 40, release=True)
        self.assertEqual(release.name, "br-isms-3.2.6.zip")
        self.assertEqual(release.read_bytes(), development.read_bytes())

    def test_development_files_are_excluded(self):
        excluded = [
            ".env", "docs/ci-checks.md", "tests/test.py", ".github/workflows/CI.yml",
            "br-isms/.env", "br-isms/src/.tmp/key.php",
            "br-isms/src/vendor/dependency.php", "br-isms/tests/test.php",
            "br-isms/src/credentials.pem",
        ]
        archive = self.build(self.tracked + excluded)
        with zipfile.ZipFile(archive) as package:
            self.assertEqual(package.namelist(), sorted(self.tracked))

    def test_required_file_cannot_be_omitted(self):
        with self.assertRaisesRegex(ValueError, "Missing required"):
            self.build([name for name in self.tracked if name != "LICENSE"])

    def test_unsafe_path_is_rejected(self):
        with self.assertRaisesRegex(ValueError, "Unsafe package path"):
            self.build(self.tracked + ["../outside.php"])

    def test_missing_source_is_rejected(self):
        (self.root / "LICENSE").unlink()
        with self.assertRaisesRegex(ValueError, "Invalid package source"):
            self.build()

    def test_invalid_version_is_rejected(self):
        (self.root / "extension.xml").write_text(
            "<extension><extension_code>br-isms</extension_code>"
            "<version>../invalid</version></extension>"
        )
        with self.assertRaisesRegex(ValueError, "Invalid extension identity or version"):
            self.build()


if __name__ == "__main__":
    unittest.main()
