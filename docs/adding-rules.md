# Adding rules

ASB 3 makes it possible for third-party plugins to add custom rules.

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
