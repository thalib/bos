I want to {TASK NAME} based on {DESING FILE = } for ai coding agent Github Coplilot

I want you to create a PRD in {PRD FILE = } for the same
follow our best pracitices

import note: do not code only create the PRD document that can be used for github issues , in file {PRD FILE}



Goal: Create a PRD (engineering-ready, GitHub-issue/PR description) for "{TASK_NAME}" based on the design in `{DESIGN_FILE}` and save it to `{PRD_FILE}`

Must rules:
- Do NOT write code. Produce only the PRD Markdown content.
- Follow project best-practices: TDD-first tests, standardized envelope responses, config/migration impact notes, audit logging, and security constraints.
- Output a ready-to-paste Markdown file with headings and bullet lists so engineers can implement the change without ambiguity.

Required PRD sections (in this exact order):
1. Title (one-line)
2. Summary (1 paragraph)
3. Background / Why
4. Scope — In-scope / Out-of-scope
5. API / User Contract (endpoints, headers, request+response JSON examples, auth)
6. Data Model & Migrations (fields, migration/file names if needed, factory & seeder tasks)
7. Validation & Security (Form Request rules, rate limits, token lifecycle)
8. Logging & Observability (what to log, request_id/correlation)
9. Testing (exact automated tests to add; file paths; edge cases)
10. Rollout & QA (manual steps, staging/production notes)
11. Acceptance Criteria (clear, testable bullets)
12. Deliverables (files to change/create, test files, config edits)
13. Risks & Mitigations
14. Developer Guidance (non-prescriptive hints, links to `{DESIGN_FILE}`)
15. PR checklist for reviewer

Formatting & constraints:
- Keep tone concise and engineering-focused.
- Provide request/response examples in JSON blocks only (no implementation code).
- If DB schema changes are required, explicitly name migration files and require updated factory + seeder in the same PR.
- Include an explicit test matrix: happy path + 3-5 edge/error cases.
- Limit length to 300–1200 words; favor clarity over verbosity.

Small examples to include in PRD:
- Login request example:
```json
{ "username": "alice@example.com", "password": "secret" }

Success envelope example:
{
  "success": true,
  "message": "Logged in",
  "data": { "access_token": "...", "refresh_token": "...", "user": { "id": 1 } }
}
```

Tips for best results (use these when creating issues):

- Attach the exact {DESIGN_FILE} path and any relevant model/migration paths.
- Call out required Form Requests (LoginRequest, RegisterRequest, RefreshRequest) and tests by filename.
- State chosen defaults (e.g., token rotation = single-use, whatsapp = E.164) if not already in {DESIGN_FILE}.
- Ask the implementer to run php artisan test and ./vendor/bin/pint before marking PR ready.

Deliverable:

One Markdown file written to {PRD_FILE} containing the sections above, ready to paste into a GitHub issue or PR description.