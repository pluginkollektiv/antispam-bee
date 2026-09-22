# antispam-bee

## What this codebase does

Antispam Bee is a WordPress plugin (v3 is a full rewrite) that classifies incoming
comments, trackbacks and pingbacks as spam or ham. PSR-4 root `AntispamBee\` → `src/`,
with **no runtime Composer dependencies** (`vendor/` is dev-only). `antispam_bee.php`
guards on PHP 7.4/DOMDocument/libxml/json, then `src/load.php::init()` iterates a
hard-coded module list on `plugins_loaded`. Subsystems: a rule engine (`src/Rules/*`,
filter `antispam_bee_rules`), post-processors/"reactions" (`src/PostProcessors/*`),
reaction handlers (`Handlers\Comment`, `Handlers\Linkback`), an admin Settings API page,
comments-list columns, and one WP-Cron job. Users are site visitors (untrusted, comment
form) and admins (`manage_options`).

## Auth shape

There is deliberately **no auth wrapper and no nonce code in `src/`** — do not report its
absence as a generic finding. Enforcement is delegated to WordPress core:

- `add_options_page(..., 'manage_options', ...)` gates the settings screen; `options.php`
  + `settings_fields()` provide the nonce; `register_setting`'s `sanitize_callback` is
  `Helpers\Sanitize::sanitize_options`.
- Only two ad-hoc capability checks exist: `Admin\DashboardWidgets::add_dashboard_count`
  and `Admin\SettingsPage::add_action_links` (both `current_user_can('manage_options')`).
- Zero occurrences of `check_admin_referer`, `wp_verify_nonce`, `check_ajax_referer`,
  `permission_callback`. There are **no REST routes, no `wp_ajax_*` handlers, no
  admin-post handlers and no custom DB tables**. Claims about those surfaces are wrong.

## Threat model

Ranked by impact: (1) stored XSS or SQLi reachable from an unauthenticated comment
submission, since `preprocess_comment` runs before any auth and its data is later
rendered in wp-admin; (2) leaking comment/IP data written to a web-served debug log;
(3) an attacker classifying their own spam as ham (bypass) — low severity, this is a
heuristic filter, so a single evadable rule is by design and not a vulnerability;
(4) SSRF/data exfiltration via the filterable outbound API URLs, which requires
attacker-controlled PHP filters and so is largely theoretical.

## Project-specific patterns to flag

- **Comment-data round trip into wp-admin.** `Admin\CommentsColumns::print_plugin_column`
  does `echo implode(',<br>', ...)` with an `EscapeOutput` ignore. Each text is escaped by
  `Helpers\SpamReasonTextHelper::get_texts_by_slugs`, but the *unknown-slug* branch passes
  the raw slug through `sprintf(esc_html_x(...), $slug)` — trace whether an attacker can
  control the `antispam_bee_reason` comment meta slug.
- **XPath built by concatenation.** `Helpers\Honeypot::inject` parses arbitrary comment-form
  markup with `DOMDocument` and builds `'//*[@id="' . $options['field_id'] . '"]'`. Flag if
  `field_id` can ever become non-constant (option, filter, or request).
- **Regex assembled from comment data.** `Rules\RegexpSpam` splices the comment author into
  patterns (now `preg_quote($subject['author'], '/')`) and runs
  `preg_match('/' . $regexp . '/isu', ...)`. Patterns from the `antispam_bee_patterns`
  filter are *not* quoted — delimiter injection, compile errors and ReDoS live here.
- **`$_POST` mutation before validation.** `Rules\Honeypot::precheck` rewrites
  `$_POST['comment']` and sets `ab_spam__hidden_field` / `ab_spam__invalid_request`, which
  `Rules\InvalidRequest::verify` and `Rules\Honeypot::verify` later trust. Flag any path
  where a client can forge those flags directly.
- **Debug log in a web-served directory.** `Helpers\DebugMode::log` writes comment data to
  `WP_CONTENT_DIR . "/asb-debug.{date}.{sha1-salted-suffix}.log"`, gated on
  `ANTISPAM_BEE_DEBUG_MODE_ENABLED`. Assess whether the suffix is genuinely unguessable.
- **IP masking correctness.** `Helpers\IpHelper::anonymize_ip` masks to `/24` (IPv4) and
  `/48` (IPv6) via `inet_pton` + binary AND, with IPv4-mapped-IPv6 unwrapping in
  `is_global_ip`/`is_ipv4_mapped`. Logic bugs here are privacy (GDPR) issues, so flag
  incorrect masking, not just injection.

## Known false-positives

- `Rules\DbSpam::verify` splices `$filter_sql` via `implode(' OR ', ...)` + `sprintf` into a
  query **before** `$wpdb->prepare($sql, $params)`. The spliced fragments are fixed
  `column = %s` placeholders and all values are parameterized — not SQLi. Same for the
  fully static unprepared queries in `Admin\CommentsColumns::filter_columns`,
  `Handlers\PluginUpdate` (`meta_key IN (...)`) and `DeleteSpamCron`'s `OPTIMIZE TABLE`.
- The `preprocess_comment` path is **intentionally unauthenticated** — rules, honeypot
  `$_POST` mutation and `Handlers\Reaction::handle_spam`'s `status_header(403); die()` are
  all by design.
- No `serialize`/`unserialize`/`maybe_unserialize` and no transients exist; remote bodies
  (iplocate, language API, wp.org `upgrade_notice`) are `json_decode` + type-checked only.
  `Admin\UpgradeNotice::render` runs remote HTML through `wp_kses` with an allowlist.
- Dev-only / not shipped: `tests/**` (incl. `tests/_stubs/{WP,includes}.php` WP function
  stubs and Playwright specs), `playwright.config.ts`, `bin/`, `phpstan-bootstrap.php`,
  `.github/workflows/`, `vendor/`, `node_modules/`. `AGENTS.md` states explicitly that
  treating `vendor/` as project code produces false findings.
- The many deliberate `phpcs:ignore` markers for `WordPress.Security.NonceVerification.*`,
  `WordPress.DB.PreparedSQL*` and `EscapeOutput` are reviewed decisions, not gaps.
