# Adding rules

ASB 3 makes it possible for third-party plugins to add custom rules. If you instead want your own
content checked by the rules that already exist, see
[Integrating your own content](integrating-own-content.md).

Each Rule is a class that extends the `ControllableBase` (if your rule has options, like for
disabling/enabling) or `Base` (if your rule works invisible in the background) class and implements
the `SpamReason` interface.

A rule's `verify()` method returns a positive score if the item looks like spam, a negative score if
the rule found a trust signal, or `0` if the result is neutral. The score is multiplied with the
rule's `$weight` and added to the overall score; an item is treated as spam if the overall score is
positive.

A rule can set the `$is_final` property to `true` if a positive result is definitive (like a filled
honeypot field). Final rules are checked before all other rules, and a positive result marks the
item as spam immediately, without evaluating the remaining rules. The finals-first ordering can be
disabled with the `antispam_bee_sort_final_rules_first` filter (return `false`), in which case all
rules are checked in their registered order.

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
defaults to comments and linkbacks. A rule extending `ControllableBase` is only applied when it is
active for the reaction type, which means it needs a default or an administrator enabling it — see
[Integrating your own content](integrating-own-content.md) for how that works.

## Payload

`verify()` receives the normalized payload, not the raw reaction. Its attributes are the same ones
listed in [Integrating your own content](integrating-own-content.md), so a rule that reads `body` and
`email` works for comments, linkbacks and any reaction type a third-party plugin registers.
