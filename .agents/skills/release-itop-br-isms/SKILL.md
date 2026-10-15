---
name: release-itop-br-isms
description: Prepare or publish an official version-tagged GitHub release of rudnerbjoern/iTop-br-isms and verify its CI-generated ZIP. Use for this repository's release workflow, not ordinary commits, merges, or other repositories.
---

# Release the iTop ISMS extension

Use the repository's tag-driven release workflow. Communicate in German and write
release text in English. Discover the checkout from the current workspace and
verify that `origin` identifies `rudnerbjoern/iTop-br-isms`; do not assume a local
path. Read its `AGENTS.md`, `.github/workflows/CI.yml`, and `docs/ci-checks.md`.
Use Windows Git through `cmd.exe` for a Windows-backed checkout.

## Authorization and preparation

Creating this skill, asking how releases work, or requesting release preparation
does not authorize publication. Obtain explicit authorization to create and push
the concrete version tag. A current instruction to publish that version or push
its release tag already supplies this authorization; do not request it again.
Do all read-only preparation before asking for any missing authorization.

1. Require a clean working tree and index. Preserve changes; do not stage, commit,
   stash, discard, or change branches over pending changes to make a release work.
2. Determine the requested version from the user and `extension.xml`. Official tags
   use `vMAJOR.MINOR.PATCH` and must match the descriptor. Do not hardcode `3.2.6`,
   increment versions, change module definitions, or repair unrelated failures as
   part of publication. If the requested version differs, stop and report the mismatch.
   Every bundled module registration must use exactly the descriptor version;
   equal minimum dependency versions do not prove matching module versions.
3. Confirm the tag-driven release workflow has been merged into `main`. Explain
   that merge artifacts are temporary and an official release requires a tag.
4. Inspect existing local and remote tags and GitHub releases for the exact version.
   Never move, replace, delete, or force-push an existing tag. If a tag or release
   already exists, inspect its commit and CI state and report what exists instead
   of creating a duplicate or overwriting assets.

## Open pull request gate

Before preparing a release, query all open pull requests in the verified repository,
including drafts, using the authenticated GitHub API. For example, use `gh api`
with `--paginate` against `repos/rudnerbjoern/iTop-br-isms/pulls?state=open&per_page=100`.
Do not restrict the query by author, branch, label, review status, or draft state.
Do not rely on a default page size or a locally cached PR list.

If any open PR exists, **end the release attempt before creating or pushing a tag**.
Report the PR numbers, titles, draft states, and links. Do not merge or close PRs
as part of this skill to satisfy this gate. Authentication, API, network, or JSON
parsing errors also block publication; they must never be interpreted as zero PRs.

Repeat the query immediately before creating the annotated tag and again before
pushing it. If an open PR appears after local tag creation, stop without pushing
or automatically deleting the local tag, and report its state. These queries are
point-in-time checks, not an atomic lock against concurrent PR creation.

## Update and review the release commit

Once branch changes are safe, switch to `main` and update by fast-forward only:

```shell
git switch main
git pull --ff-only
```

Verify that local `HEAD` equals `origin/main`, the checkout remains clean, and the
remote identity is correct. Stop on divergence, local-only commits, or pull errors;
do not reset or rebase to force agreement. Record the full candidate commit SHA.

Re-read the version and release workflow from this updated commit. Before any
tag creation, run this bundled check from the repository root with PHP 8.3:

```shell
php .agents/skills/release-itop-br-isms/scripts/check-versions.php
```

It evaluates every discovered runtime `module.*.php` definition in its own PHP
process, verifies its registered module identity/version against `extension.xml`,
and requires both ISMS and catalog modules. It also covers additional sibling
modules. A missing module, unreadable/malformed definition, missing PHP or DOM,
ambiguous registration, or any version mismatch **must end the release attempt**.
Report the affected file, actual version, and required version. Do not proceed to
tagging or pushing, silently skip this check, or edit versions to make it pass.
Do not compare unrelated dependency minimums (such as iTop core or external
extensions) for equality with the bundle version.

Recheck that the proposed tag is `v` followed by this same version. Inspect GitHub
checks for that exact SHA; missing checks are not successful checks. Do not bypass
failed quality/security jobs. Run the focused release policy tests and execute
`tests/check-release.py` with `RELEASE_TAG` set to the proposed tag, using the
repository's Python interpreter. Do not run database integration suites merely
to publish. The workflow also rechecks the tagged commit before publication.

If authorization is still missing, present the repository, version tag, exact
commit SHA, checks, and expected ZIP publication, then request approval. Do not
create the tag or push it until approval arrives. Verify the candidate SHA and
clean state again immediately before creating the tag. If the candidate commit
changed since validation, stop and repeat the version checks against the new
candidate before proceeding.

## Create and push the annotated tag

Substitute the verified version below; `v3.2.6` is only an example:

```shell
git tag -a v3.2.6 -m "Release 3.2.6"
git push origin v3.2.6
```

Push only the intended tag, not all tags or unrelated branches. This push triggers
an externally visible GitHub Release with a ZIP after the required CI jobs pass.
No direct commit or push to `main` is needed.

After a push failure, inspect local and remote state before retrying. An annotated
tag's object SHA differs from its target commit: verify the remote tag's peeled
commit (`refs/tags/<tag>^{}`) against the recorded candidate SHA. Do not blindly
retry publication or repair an existing remote tag.

## Verify publication

Find the GitHub Actions run for the exact tag and candidate SHA. Observe it until
completion, reporting pending status if interrupted. A successful tag push does
not prove a successful release. On failure, report the failing job and stop;
do not weaken gates, change versions, replace tags, or overwrite a release to hide it.

On success, verify the published release targets the intended tag, is neither a
draft nor a prerelease, and has the expected nonempty extension ZIP asset. Report
the release URL, version tag, target commit, workflow outcome, and asset name.
Distinguish configuration checks, completed CI, and untested iTop runtime behavior.
If the run is still pending, state that publication has not yet been verified.
