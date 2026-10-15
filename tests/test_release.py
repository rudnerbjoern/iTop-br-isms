"""Regression tests for the official release version policy."""

import importlib.util
from pathlib import Path
import sys
import unittest

sys.dont_write_bytecode = True
SPEC = importlib.util.spec_from_file_location(
    "release_check", Path(__file__).with_name("check-release.py")
)
CHECK = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(CHECK)


class ReleaseTests(unittest.TestCase):
    def test_matching_stable_version(self):
        CHECK.validate_tag("v3.2.6", "3.2.6")
        CHECK.validate_tag("v0.1.0", "0.1.0")

    def test_mismatched_version(self):
        with self.assertRaisesRegex(ValueError, "must match"):
            CHECK.validate_tag("v3.2.7", "3.2.6")

    def test_invalid_or_unstable_tag(self):
        for tag in ["", "3.2.6", "v3.2", "v03.2.6", "v3.2.6-rc.1", "v3.2.6+build", "main", "v3.2.6\n"]:
            with self.subTest(tag=tag), self.assertRaisesRegex(ValueError, "stable"):
                CHECK.validate_tag(tag, "3.2.6")


if __name__ == "__main__":
    unittest.main()
