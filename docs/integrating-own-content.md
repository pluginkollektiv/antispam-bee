# Integrating your own content

Plugins that handle their own content — contact forms, registrations, custom post types — can have
Antispam Bee classify that content. This page describes the supported way to do that.

`\AntispamBee\Api\SpamCheck` is the entry point. Everything outside the `\AntispamBee\Api` namespace
and the documented hooks is internal and may change in any release, so please do not call
`\AntispamBee\Handlers\Rules` or `\AntispamBee\Handlers\PostProcessors` directly.

## Checking an item

```php
if ( ! class_exists( '\AntispamBee\Api\SpamCheck' ) ) {
    return;
}

$result = \AntispamBee\Api\SpamCheck::check(
    [
        'author' => $name,
        'body'   => $message,
        'email'  => $email,
        'url'    => $website,
    ],
    'my_plugin_form'
);

if ( $result->is_spam() ) {
    // Store the slugs, not the texts – see “Spam reasons” below.
    $reasons = $result->get_reasons();
}
```

The item is normalized before the rules see it, so you only need to pass the attributes your content
actually has. Recognized attributes:

| Attribute   | Description                                                                 |
|-------------|-----------------------------------------------------------------------------|
| `author`    | Name of the author.                                                         |
| `body`      | The content to check.                                                       |
| `email`     | Email address of the author.                                                |
| `url`       | A URL submitted with the content, like a website field.                     |
| `host`      | Host of that URL. Derived from `url` when omitted.                          |
| `ip`        | IP address of the author. Defaults to the IP of the current request.        |
| `useragent` | User agent of the author. Defaults to the one of the current request.       |
| `post_id`   | ID of a related post, if any.                                               |

Anything you leave out defaults to an empty value. Use the `antispam_bee_api_payload` filter if you
need to adjust the normalized payload.

`check()` returns an `\AntispamBee\Api\CheckResult`:

* `is_spam()` – whether the item was classified as spam.
* `get_reasons()` – slugs of the rules that flagged it.
* `get_reason_texts()` – human-readable texts for those slugs.
* `get_payload()` – the normalized payload the rules were applied to.
* `was_evaluated()` – whether any rule ran at all, see below.

## Nothing is checked until a rule is active

A result can report no spam simply because no rule was active for your reaction type, and that is
the most common reason an integration appears to do nothing. Use `was_evaluated()` on a result, or
`SpamCheck::has_active_rules()` up front, to tell that apart from a clean item — for example to warn
an administrator while setting things up:

```php
if ( ! \AntispamBee\Api\SpamCheck::has_active_rules( 'my_plugin_form' ) ) {
    // No rule is enabled for this reaction type, nothing will be caught.
}
```

## Using your own reaction type

Reusing `comment` means your content is checked with the rules and options the site owner configured
for comments. That is a reasonable starting point, but a custom reaction type gets its own settings
tab, so rules can be enabled for your content independently.

Three hooks are involved:

```php
// 1. Register the reaction type so it has a readable name.
add_filter(
    'antispam_bee_reaction_types',
    function ( array $types ): array {
        $types['my_plugin_form'] = __( 'My plugin', 'my-plugin' );

        return $types;
    }
);

// 2. Opt existing rules into the reaction type. This is also what creates its settings tab.
add_filter(
    'antispam_bee_rule_supported_types',
    function ( array $supported_types, string $slug ): array {
        if ( in_array( $slug, [ 'asb-bbcode', 'asb-regexp' ], true ) ) {
            $supported_types[] = 'my_plugin_form';
        }

        return $supported_types;
    },
    10,
    2
);

// 3. Declare which of those rules should be active out of the box.
add_filter(
    'antispam_bee_default_options',
    function ( array $defaults ): array {
        $defaults['my_plugin_form'] = [
            'rule_asb_bbcode_active' => 'on',
            'rule_asb_regexp_active' => 'on',
        ];

        return $defaults;
    }
);
```

Step 3 matters: most rules are only applied when they are active for the reaction type, and a fresh
reaction type has no stored options, so without defaults nothing is checked until an administrator
enables rules on the new settings tab. Defaults only apply to reaction types that are absent from the
stored options — once a reaction type has been saved, the stored state wins, so a rule that an
administrator deliberately disabled stays disabled.

Post-processors can be opted in the same way, via `antispam_bee_post_processor_supported_types`.

## Spam reasons

`get_reasons()` returns rule slugs. Store those, and resolve them to texts with
`get_reason_texts()` at the moment you display them — the slug-to-text map is populated on the `init`
action, so resolving too early yields placeholders.

For slugs you stored earlier and no longer have a result for, `SpamCheck::get_reason_texts()` resolves
them directly:

```php
$texts = \AntispamBee\Api\SpamCheck::get_reason_texts( $stored_slugs );
```

## AJAX and REST requests

Antispam Bee skips its module initialization during AJAX requests, and rules register themselves
during that initialization. If your submission is handled over AJAX, no rule will be registered and
`check()` will find nothing to apply. Allow initialization for your own request:

```php
add_filter(
    'antispam_bee_disallow_ajax_calls',
    function ( bool $disallow ): bool {
        $action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );

        return 'my-plugin-submit' === $action ? false : $disallow;
    }
);
```

Register this filter early — while your plugin file loads, not on `plugins_loaded` — because
Antispam Bee evaluates it during its own `plugins_loaded` callback.

REST requests are not AJAX requests as far as `wp_doing_ajax()` is concerned, so they are not
affected by this at all.

If you enable the [debug mode](https://antispambee.pluginkollektiv.org/documentation/), Antispam Bee
logs when it finds no active rule for a reaction type, which makes both of these cases visible.

## Reacting to spam the way Antispam Bee does

`check()` only classifies. It does not count the item, store a reason, notify anybody, or delete
anything. If you handle storage and notification yourself, that is all you need.

To have Antispam Bee react as it does to its own spam — updating the spam counter and the statistics,
sending notifications — pass the result to the post-processors:

```php
if ( $result->is_spam() ) {
    $item = \AntispamBee\Api\SpamCheck::post_process( $result, $my_item, 'my_plugin_form' );

    if ( isset( $item['asb_marked_as_delete'] ) ) {
        // A post-processor decided this item should not be stored at all.
    }
}
```

Without this, the spam your integration catches will not show up in Antispam Bee’s spam count or
dashboard widget.

## Adding your own rules

See [Adding rules](adding-rules.md).
