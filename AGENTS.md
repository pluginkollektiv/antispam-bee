# AI Agent Guidelines

General rules for AI agents contributing to this project.

## Code quality — verify before every commit

| Check                   | Command                                      |
|-------------------------|----------------------------------------------|
| PHP code style          | `composer cs`                                |
| PHPStan static analysis | `composer phpstan`                           |
| PHP unit tests          | `composer test:unit`                         |
| E2E tests               | `npm run env:start`, then `npm run test:e2e` |

Fix code style violations automatically with `composer csfix`.

Target PHP 7.2+. Avoid syntax introduced in PHP 7.4 or later: typed properties, arrow functions (`fn =>`), `match` expressions, null-coalescing assignment (`??=`). The `phpcs.xml` ruleset enforces this via PHPCompatibilityWP.

## WordPress & security conventions

- **Capability checks** — gate every admin action with `current_user_can( 'manage_options' )` before reading `$_GET`/`$_POST` or writing data.
- **Nonces** — verify with `check_admin_referer()` (form submissions) or `wp_verify_nonce()` (AJAX); generate with `wp_nonce_field()` / `wp_create_nonce()`.
- **Sanitise input** — use `sanitize_text_field( wp_unslash( $value ) )`, `sanitize_key()`, `absint()`, etc. on all untrusted input at the point of reading.
- **Escape output** — use `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses()` at the point of output; never store pre-escaped values.
- **Text domain** — always `'antispam-bee'` (matches the plugin slug); do not use a variable or a different string.

## Git workflow

- Always work on a **feature branch** based on `v3`
- Never commit directly to `v3` or `master`
- Use conventional commit messages: `feat:`, `fix:`, `test:`, `refactor:`, `chore:`, etc.

## Ignore / export files

Three files control what is excluded from VCS and distributions. Keep them in sync when adding new dotfiles or dev-only assets.

### `.gitignore`

Excludes local artifacts from git tracking.

- Directories use a trailing `/` (e.g. `/build/`) — required to match directories only, not same-named files
- Use `/` prefix for root-level entries (e.g. `/.idea/`)
- Use `/**/` prefix for entries that must also match in subdirectories (e.g. `/**/node_modules/`, `/**/vendor/`)
- Keep each section (`# Directories`, `# Files`) sorted alphabetically

### `.distignore`

Excludes files from `wp dist-zip` plugin packages. WP-CLI does not support glob syntax — use plain root-relative paths only.

- No trailing `/` on directories (not needed by WP-CLI)
- Always use `/` prefix (e.g. `/bin`, `/node_modules`)
- Keep each section (`# Directories`, `# Files`) sorted alphabetically

### `.gitattributes`

Excludes tracked files from `git archive` exports via `export-ignore`.

- Only tracked files and directories need an entry here — gitignored paths are already absent from archives
- Always use `/` prefix (e.g. `/.github export-ignore`)
- No trailing `/` on directories (not needed)
- Keep each section (`# Directories`, `# Files`) sorted alphabetically

## Pull requests

- Base branch: `v3`
- Include a short summary of what changed and why
- Add test steps when the change affects user-facing behavior; pure code style fixes do not need them
