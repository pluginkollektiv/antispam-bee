## Filters

### `antispam_bee_rule_supported_types`

Filter that allows to modify the reaction types a rule supports. For example, if you only want the BB Code rule to be present for trackbacks, you can use the following code snippet:

```php
add_filter( 'antispam_bee_rule_supported_types', function( $supported_types, $slug ) {
	if ( $slug !== 'asb-bbcode' ) {
		return $supported_types;
	}

	$supported_types = [ 'trackback' ];
	return $supported_types;
}, 10, 2 );
```
