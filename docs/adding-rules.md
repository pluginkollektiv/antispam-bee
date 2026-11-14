# Adding rules

ASB 3 makes it possible for third-party plugins to add custom rules. If you instead want your own
content checked by the rules that already exist, see
[Integrating your own content](integrating-own-content.md).

Each rule is a class that extends `ControllableBase` (if your rule has options, like for
disabling/enabling) or `Base` (if your rule works invisible in the background, with no settings UI
of its own). A rule can additionally implement the `SpamReason` interface to contribute a
human-readable reason shown in the spam comments list and in notification emails — it is not
required for every rule. `ApprovedEmail` and `ValidGravatar`, for example, are `ControllableBase`
rules that do not implement it, because a trusted result needs no spam reason.

A rule's `verify()` method returns a positive score if the item looks like spam, a negative score if
the rule found a trust signal, or `0` if the result is neutral. The score is multiplied with the
rule's `$weight` and added to the overall score. By default an item is treated as spam once the
overall score is positive, but this is configurable through two filters:

```php
// Only treat a reaction as spam once the score passes 2, not just above 0.
add_filter( 'antispam_bee_spam_threshold', fn() => 2.0 );

// Give a reaction the benefit of the doubt down to -1 instead of 0.
add_filter( 'antispam_bee_no_spam_threshold', fn() => -1.0 );
```

`antispam_bee_no_spam_threshold` (default `0.0`) is checked first: a score at or below it is always
ham, regardless of the individual rule results. `antispam_bee_spam_threshold` (default `0.0`) is
checked next: a score at or above it — and still positive — is spam. Between the two thresholds the
result is neutral and treated as ham.

A rule can set the `$is_final` property to `true` if a positive result is definitive (like a filled
honeypot field). Final rules are checked before all other rules, and a positive result marks the
item as spam immediately, without evaluating the remaining rules. The finals-first ordering can be
disabled with the `antispam_bee_sort_final_rules_first` filter (return `false`), in which case all
rules are checked in their registered order.

## A complete example

A minimal rule that scores a comment as spam when its content contains a word from a configurable
denylist:

```php
<?php
namespace My_Plugin\Rules;

use AntispamBee\Helpers\Settings;
use AntispamBee\Rules\ControllableBase;

class My_Rule extends ControllableBase {

    protected static $slug   = 'my-plugin-denylist';
    protected static $weight = 1;

    public static function verify( array $item ): int {
        $denylist = array_filter( array_map( 'trim', explode( ',', self::get_denylist( $item['reaction_type'] ) ) ) );

        foreach ( $denylist as $word ) {
            if ( '' !== $word && false !== stripos( $item['body'] ?? '', $word ) ) {
                return 1;
            }
        }

        return 0;
    }

    public static function get_options(): array {
        return [
            [
                'type'        => 'textarea',
                'label'       => __( 'Denylisted words, comma-separated', 'my-plugin' ),
                'option_name' => 'denylist',
            ],
        ];
    }

    private static function get_denylist( string $reaction_type ): string {
        return (string) Settings::get_option( static::get_option_name( 'denylist' ), $reaction_type ) ?: '';
    }

    public static function get_name(): string {
        return __( 'My denylist rule', 'my-plugin' );
    }
}
```

Registered the same way as any other rule (see below), it picks up an automatic "active" checkbox
plus the `denylist` textarea declared in `get_options()`, both rendered on the settings page.

## Registering a rule

Rules are registered by adding their class name to the `antispam_bee_rules` filter. Extending `Base`
or `ControllableBase` gives you an `init()` method that does this, so calling it is enough:

```php
add_action(
    'plugins_loaded',
    function (): void {
        if ( class_exists( '\AntispamBee\Rules\Base' ) ) {
            \My_Plugin\Rules\My_Rule::init();
        }
    }
);
```

The `asb-` slug prefix is reserved for the rules that ship with Antispam Bee. Rules from other
plugins using that prefix are rejected, so pick a prefix of your own.

Which reaction types a rule applies to is declared with the `$supported_types` property, which
defaults to comments and linkbacks. Other plugins can also opt your rule into a reaction type they
register themselves, through the `antispam_bee_rule_supported_types` filter — see
[Integrating your own content](integrating-own-content.md#using-your-own-reaction-type) for the
filter's signature and an example. A rule extending `ControllableBase` is only applied when it is
active for the reaction type, which means it needs a default or an administrator enabling it — see
[Integrating your own content](integrating-own-content.md) for how that works.

## Options

A `ControllableBase` rule automatically gets an "active" checkbox on the settings page, scoped per
reaction type (stored as the `rule_{slug}_active` option — see
[Integrating your own content](integrating-own-content.md) for how activation defaults are set).
Override `get_options()` to add further settings fields of your own, as in the example above. Each
entry is an array describing one field:

- `type` — the field type (e.g. `checkbox`, `text`, `textarea`).
- `label` — the field's label. `label_kses` can allow specific HTML tags in it (see `CountrySpam`
  for an example that links to an external reference from its label).
- `option_name` — the option's name, without the `rule_{slug}_` prefix that's added automatically.
- `placeholder` — optional placeholder text.
- `sanitize` — an optional callback the submitted value is passed through before it is stored.
- `valid_for` — optional; restricts the field to a single reaction type's settings tab instead of
  showing it on every tab the rule supports.

Read a declared option back with `Settings::get_option( static::get_option_name( 'option_name' ), $reaction_type )`
— `get_option_name()` is inherited from `ControllableBase` and adds the `rule_{slug}_` prefix the
option is actually stored under.

If your rule should not get the automatic "active" checkbox at all — for example because it is
always on, or controlled some other way — set `protected static $only_print_custom_options = true;`
and `get_options()` becomes the only thing rendered for it.

A rule (of either base class) can also set `protected static $is_invisible = true;` to keep it out
of places that list rules by name for a human, such as the reasons shown by the "delete for reasons"
post-processor. `LinkbackFromMyself` is an example — its result is a trust signal, not something an
administrator needs to see called out as a reason.

## Payload

`verify()` receives the normalized payload, not the raw reaction. Its attributes are the same ones
listed in [Integrating your own content](integrating-own-content.md), so a rule that reads `body` and
`email` works for comments, linkbacks and any reaction type a third-party plugin registers.
