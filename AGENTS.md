# Project instructions

- Communicate in German; write code, documentation, and commits in English.
- Target iTop 3.2; keep the catalog independent and English/German dictionaries aligned.
- Use Git history for modification dates; do not add date-based `@version` tags. Keep module and extension versions in their metadata; use `@since` only for an established introduction version.
- Preserve user changes; modify staged files only when explicitly requested.
- Never stage, commit, or push without authorization. Use feature branches and GitHub pull requests, never direct commits to `main` or `master`.
- Follow [commit instructions](.github/commit-message-instructions.md), `.editorconfig`, and `.gitattributes`.
- Use this repository's own instructions and configuration; keep temporary files in its ignored `.tmp/`.
- Run focused local checks; database tests require explicit authorization and disposable environments.
- When creating or updating GitHub issues and pull requests, use the applicable repository template:
  - Bug reports: [.github/ISSUE_TEMPLATE/bug_report.md](.github/ISSUE_TEMPLATE/bug_report.md).
  - Feature requests: [.github/ISSUE_TEMPLATE/feature_request.md](.github/ISSUE_TEMPLATE/feature_request.md).
  - Pull requests: [.github/pull_request_template.md](.github/pull_request_template.md).
- Follow the templates when using CLI tools or APIs as well as the GitHub interface. Apply issue labels declared in template metadata explicitly when the tool does not apply them automatically. Preserve existing relevant content and labels when updating an issue or pull request; omit inapplicable sections and never invent results or acceptance.
