# AI Agent Guidelines

General rules for AI agents contributing to this project.

## Code quality — verify before every commit

| Check                   | Command              |
|-------------------------|----------------------|
| PHP code style          | `composer cs`        |
| PHPStan static analysis | `composer analyse`   |
| PHP unit tests          | `composer test:unit` |
| E2E tests               | `npm run test:e2e`   |

Fix code style violations automatically with `composer csfix`.

## Git workflow

- Always work on a **feature branch** based on `develop`
- Never commit directly to `develop` or `master`
- Use conventional commit messages: `feat:`, `fix:`, `test:`, `refactor:`, `chore:`, etc.

## Pull requests

- Base branch: `develop`
- Include a short summary of what changed and why
- Add test steps when the change affects user-facing behaviour; pure code style fixes do not need them
