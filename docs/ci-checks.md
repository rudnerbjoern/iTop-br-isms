# CI checks

The workflow is defined in [CI.yml](../.github/workflows/CI.yml).
Commands below run from the repository root.

## PHP and XML source checks

Run the same checks locally and in the GitHub pull request workflow:

```shell
php tests/check-source.php
```

PHP 8.3 or later with the DOM extension and process execution enabled is required.
The workflow uses PHP 8.3 and requires no installed iTop instance or database.

The checks validate PHP syntax, XML syntax without network access, and required
registration metadata in every discovered `module.*.php` file. Each module is
evaluated in a separate PHP process using minimal installer registration doubles.
The documentation metadata keys must exist and contain strings; empty strings
are valid. Generated, dependency, and temporary directories are excluded.

These checks do not establish XSD validity, successful iTop model compilation,
module selection, effective permissions, installation/upgrade compatibility, or
runtime behavior. Compilation and runtime checks require a separate disposable
iTop integration environment; XSD validation is covered by the separate job below.
The accepted permission discrepancy tracked in issue #17 remains outside this
source-only workflow.

The workflow runs for pull requests targeting `main`, including drafts, and pushes
to `main`, as well as version tag pushes. It can also be started manually after publication on the default branch. It uses a
read-only repository token and does not retain checkout credentials. Publishing
the workflow does not make its check mandatory; required checks are configured
separately in GitHub branch protection or rulesets.

## Markdown lint

Install the locked development dependencies and run the same command as CI:

```shell
npm ci --ignore-scripts --cache .tmp/npm-cache
npm run lint:md
```

Use Node.js 24 for parity with CI. The dependency is `markdownlint-cli`, not
`markdownlint-cli2`. The shared `.markdownlint.json` enables default rules except
line length (MD013), emphasis as headings (MD036), and a required first H1
(MD041), since issue and pull request templates are document fragments.
Table columns and delimiter rows must be aligned (MD060). The scan includes
hidden directories such as `.github`; `.markdownlintignore` excludes dependency,
generated, and temporary directories. Lint findings fail the independent
Markdown job without waiting for PHP/XML checks.

## XML schema validation

The independent XML job installs `xmllint` from Ubuntu's `libxml2-utils` package.
It downloads the published iTop 3.2 datamodel and extension descriptor schemas
from `rudnerbjoern.github.io/iTop-schema` into `.tmp/xsd-ci/`, then runs:

```shell
python3 tests/check-xml-schema.py
```

For local execution, install Python 3 and `xmllint`, and download the two schemas
using the commands in `.github/workflows/CI.yml`. No Python packages are required.
The checker discovers XML outside generated/dependency directories, checks its
schema reference, and calls `xmllint --nonet --noout --schema` with the appropriate
local XSD. Download failures, unknown document types, missing schema references,
and schema violations fail the job. Sample data (`Set` or `itop_data` in `data/`)
has no configured XSD and receives only the existing syntax check; the checker
reports that limit.

Schemas are retrieved afresh for each run, so upstream schema updates can affect
results without a repository change. XSD validation does not establish successful
iTop model compilation, installation, or runtime behavior.

## Dictionaries and translations

The independent dictionary job uses PHP 8.3 and runs:

```shell
php tests/check-dictionaries.test.php
php tests/check-dictionaries.php
```

For each discovered module, English (`EN US`) and German (`DE DE`) dictionaries
must exist and register the same keys. The check fails on duplicate keys inside
an array, across registration calls, across files, or across modules in the same
language. Token inspection detects duplicates before PHP silently overwrites array
entries. Dictionary keys must be string literals and values must be strings.
Empty strings are allowed, for example for optional help texts.

The check also verifies that the registered locale matches the filename language.
It uses a minimal `Dict::Add` double without iTop or a database. Matching keys do
not establish linguistic quality, UI rendering, or completeness against every
possible runtime dictionary lookup.

The regression checks use synthetic dictionaries under a unique `.tmp/` directory
and remove their own fixtures afterward. They verify valid optional empty values,
duplicate keys, missing translations, incorrect locales, invalid value types,
and missing dictionary files before CI checks the actual module dictionaries.

## Spelling

The independent spelling job uses Node.js 24 and the locked `cspell` CLI:

```shell
npm ci --ignore-scripts --cache .tmp/npm-cache
npm run lint:spelling
```

The shared `cspell.json` checks Markdown in English and German, including hidden
folders such as `.github`. The existing PHP dictionary and project words remain
available. Fenced code blocks, inline code, and URLs are ignored. Git-ignored
files and `.git/` are excluded. Unknown words and
configuration errors fail the job; a successful spelling check does not validate
the meaning or completeness of translations. Review spelling findings before
adding legitimate project terminology to the shared word list.

## Workflow validation

The workflow change detector compares the PR head against its merge base using
Git history. Only changes under `.github/workflows/` start the dependent
`actionlint` job; documentation-only or extension-only changes skip it. Added,
modified, renamed, and deleted workflow files trigger the check. Comparison
errors fail detection rather than being treated as no changes. Other CI jobs
remain independent. Manual workflow runs skip this PR-specific check.

The job downloads `actionlint` 1.7.12 from its official GitHub release, verifies
the pinned SHA-256 checksum, and validates all remaining workflow files.
It checks workflow structure and GitHub expressions. Optional ShellCheck and
Python lint integrations are disabled explicitly; this job does not claim to
validate embedded scripts with those separate tools.

For a local check with `actionlint` 1.7.12 installed:

```shell
actionlint -shellcheck= -pyflakes=
```

## Secret scanning

The independent Gitleaks job runs on every PR workflow, push to `main`, version tag push, and manual run, regardless
of documentation checks or workflow change detection. It downloads Gitleaks
8.30.1 from the official GitHub release and verifies a pinned SHA-256 checksum.
No Gitleaks Action license or additional repository secret is required.

Checkout fetches complete history. The scan uses `gitleaks git --log-opts="--all"`
to inspect all fetched refs, including historical commits. Findings and scanner
errors fail the job. The default detection rules apply without a baseline or
added exclusions. This scan does not inspect untracked or uncommitted files.

Secret values are fully redacted with `--redact=100`. The JSON report stays in
`.tmp/gitleaks/report.json` on the temporary runner and is not uploaded as an
artifact; redaction does not remove all file or commit metadata from reports.
The scanner is CI-only and is not added to the local test commands. Configuration
validation alone does not establish a completed scan or a clean history.

## Download package after merge

A push to `main`, normally produced by merging a PR, starts CI and builds a ZIP
after PHP/XML source checks, Markdown lint, schema validation, dictionary checks,
spelling, and secret scanning succeed. Failed checks block publication; workflow
validation remains PR-specific and is not a dependency of packaging. New commits
to `main` do not cancel older runs, allowing each successful merge its own package.
PR and manual runs do not upload packages.

Open **Actions → CI → the run for the merge → Artifacts** to download the ZIP.
The artifact is retained for 30 days, subject to repository policy. Its filename
contains the descriptor version and the first 12 characters of the commit SHA,
for example `br-isms-3.2.6-0123456789ab.zip`. It is a development build, not a
GitHub Release. The upload preserves the ZIP directly without another archive.

The ZIP contains `extension.xml`, `README.md`, `LICENSE`, and both runtime
modules. Module metadata, data models, dictionaries, images, source code, sample
data, and addon resources are included from tracked files using an allowlist.
Tests, CI configuration, documentation under `docs/`, dependency directories,
hidden files, and temporary output are excluded. Missing required files, unsafe
paths, invalid descriptor versions, or archive verification errors fail the build.
The builder checks the ZIP inventory, integrity, and file bytes against sources.

To build locally from tracked files in the working tree:

```shell
python3 tests/test_build_package.py
python3 tests/build-package.py
```

Outputs stay under `.tmp/packages/`. A local build may include uncommitted edits
to tracked files; its commit suffix identifies `HEAD`, not a clean working tree.
CI checks out the exact merged commit. Packaging verifies file contents and
structure; it does not establish installation or runtime compatibility.

## Official version releases

The same `CI.yml` handles official releases; a separate workflow is not required.
Pushing a tag such as `v3.2.6` runs the checks again on the tagged commit. After
the same quality and security jobs required for merge artifacts succeed, the
release job builds the ZIP and publishes it as an asset of a GitHub Release.
Official release ZIPs use a version-only name, such as `br-isms-3.2.6.zip`,
without a commit suffix. The release job passes `--release` to the package builder;
development artifacts retain their commit suffix to distinguish builds.
Release notes are generated by GitHub using `.github/release.yml`. Merged pull
requests are grouped by the existing `enhancement`, `bug`, and `documentation`
labels into new features, bug fixes, and documentation/translations. A final
catch-all category includes every remaining pull request, including dependency
updates without a matching category label. No authors or labels are excluded.

Release assets have no configured 30-day
expiration, unlike workflow artifacts; they remain until removed with the asset
or release.

Official tags must follow `vMAJOR.MINOR.PATCH`, match `extension.xml`, and point
to a commit included in `main`. Invalid versions, mismatches, or commits outside
`main` fail the release job. Tags with suffixes for release candidates or build
metadata are not accepted as official releases. The workflow requires an existing
remote tag and does not create or move tags, overwrite releases, or change versions.

Only the release job receives `contents: write`, using the built-in GitHub token.
PRs, branch pushes, and manual runs cannot publish a release. Creating and pushing
a version tag is an explicit publication action; this configuration does not
create a tag or publish a release by itself. As with merge artifacts, existing
quality failures block release publication.

The release version guard can be checked locally without publishing:

```shell
python3 tests/test_release.py
```

## Dependabot

Dependabot runs as a GitHub service alongside CI. The configuration in
`.github/dependabot.yml` schedules npm and GitHub Actions version checks every
Monday at 06:00 in the Europe/Berlin timezone, with up to five open version-update
PRs per ecosystem. It becomes active after the configuration reaches the default
branch. Actions pinned to commit SHAs are covered by the GitHub Actions ecosystem.

Repository-level Dependabot alerts and automated security updates are enabled.
Security updates respond to known vulnerable dependencies independently of the
weekly version-update schedule. Dependabot proposes fixes through PRs rather
than pushing changes directly to `main`; the main ruleset requires the CI checks
before merging. Updates are not automatically merged.

This does not scan PHP application code, Python standard library code, runtime
container contents, or the scanner binaries downloaded by workflow shell steps.
Those binaries and their checksums still require reviewed updates. A configured
schedule or enabled alerts do not establish that a scan ran or that every
dependency is free of vulnerabilities. Open Dependabot PRs also block publication
through the release skill's open-PR gate.
