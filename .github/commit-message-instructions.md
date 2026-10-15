# Commit-message generation

Generate a message only for the staged change in this ISMS extension repository.
The project targets iTop 3.2 and contains the ISMS and reference catalog modules.
Use English and this header:

```text
:<icon>: <type>(<scope>): <imperative outcome>
```

Requirements:

- Never commit directly on `main` or `master`. Commit on a separate feature
  branch and submit the change through a GitHub pull request.
- Keep the raw header, including the shortcode, at or below 80 characters.
- Use lower-case `type` and `scope`; require one concise capability scope.
- Start the summary with a lower-case imperative verb and omit its final period.
- Describe the outcome, not a file list.
- Use exactly one deterministic type and its matching icon:
  - `:sparkles: feat` for new ISMS or catalog capabilities.
  - `:bug: fix` for corrected behavior.
  - `:recycle: refactor` for restructuring without intended behavior changes.
  - `:zap: perf` for performance improvements.
  - `:test_tube: test` for test coverage or validation behavior.
  - `:memo: docs` for documentation-only changes.
  - `:package: build` for dependencies, packaging, or build tooling.
  - `:gear: ci` for automated pipeline behavior or CI infrastructure.
  - `:wrench: chore` for maintenance that fits none of the types above.
- Choose the type for the primary staged outcome. Accompanying tests and
  documentation do not change the type of a feature or fix.
- Prefer a domain scope such as `scope`, `assets`, `risks`, `controls`,
  `acceptance`, `reviews`, `applicability`, `catalog`, `evidence`, or `profiles`.
  Use the affected capability even if its implementation is XML or PHP.
- Use a technical scope such as `setup`, `packaging`, `tests`, `dependencies`,
  `tooling`, or `ci` only for a genuinely technical or cross-cutting change.
  Do not use a filename, programming language, or full module identifier as scope.
- Omit the body for a small self-explanatory change.
- Otherwise add `Why:` followed by the motivation, then `Changes:` with only
  the material effects. Mention module dependencies, installation compatibility,
  or data preservation when relevant. Do not invent manual upgrade steps for
  changes handled automatically by the iTop installer.
- Add `Refs: #<issue>` for a related GitHub issue or `Fixes: #<issue>` when the
  staged change resolves it. Use `owner/repository#<issue>` for another repository.
  Include references only when established by the staged change or associated task.
- Preserve existing planning references when present. Do not introduce a planning
  identifier convention or fabricate identifiers.
- Add a `BREAKING CHANGE:` footer for an incompatible contract or migration,
  explaining the incompatibility and any required action.
- Never invent verification results, issue identifiers, effects, or rationale.
  Syntax checks are not XSD validation or iTop model compilation. Source contracts
  and ORM doubles do not establish installed behavior or effective permissions.
  Pipeline configuration is not evidence of an executed CI run.
- Never include secrets, personal data, customer/CMDB records, database contents,
  generated iTop configuration, or sensitive diagnostic details.
