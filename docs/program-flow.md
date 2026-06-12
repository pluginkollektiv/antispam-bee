# Program flow

1. Entry point for the plugin is, of course, the `antispam_bee.php`. There we check a few requirements like PHP version and PHP extensions. If everything looks good, we proceed.
2. Meeting all requirements in `antispam_bee.php`, the `src/load.php` file is included. Besides registering hooks for doing things on activation/deactivation/uninstall/update, this is the place where the modules of ASB are loaded.
   In the `init` method we loop over a class array, and call the `init` method on all classes if it exists.
   The `init` methods in the module classes contain `add_action` and `add_filter` calls to hook into WordPress and do their thing. What happens next depends on the kind of request that is done.
	1. **A reaction (comment/linkback/…) coming in:**\
	   Handling comment and linkback requests happens in the classes `\AntispamBee\Handlers\Comment` and `\AntispamBee\Handlers\Linkback`. If you look in the `src/load.php` you can see both as part of the modules array.
	   The classes extend the abstract `\AntispamBee\Handlers\Reaction` class that comes with a basic `init` method, being good enough for the linkback reactions.
	   In the `process` methods of the classes you can see the process of applying rules to a reaction and, if the rules come to the result it is spam, how the post processors are applied.
		1. To apply all active comment rules to a reaction, all you have to do is:
		   ```php
		   $rules   = new \AntispamBee\Handlers\Rules( 'comment' );
		   $is_spam = $rules->apply( $reaction );
		   ```
		   Where `$reaction` is an array of comment data. The `apply` method returns a boolean indicating if the reaction is spam or not.
		2. If it is spam, you can apply the post processors (code snippet from the `\AntispamBee\Handlers\Reaction::process` method):
		   ```php
		   if ( $is_spam ) {
		       $item = PostProcessors::apply( 'comment', $reaction, $rules->get_spam_reasons() );
		       if ( ! isset( $item['asb_marked_as_delete'] ) ) {
		           add_filter(
		               'pre_comment_approved',
		               function () {
		                   return 'spam';
		               }
		           );

		           return $reaction;
		       }

		       status_header( 403 );
		       die( 'Spam deleted.' );
		   }
		   ```
		   The `apply` method of `\AntispamBee\Handlers\PostProcessors` returns the Item again with added data:
			* In `$item['asb_reasons']` the reasons are stored that are provided as the third parameter to the `apply` method.
			* `$item['reaction_type']` holds the content type that is passed as the first parameter to the `apply` method.
			* If `$item['asb_marked_as_delete']` is set, one or more post processors were applied that indicate that the comment should not be stored.
	2. **The user visiting the dashboard:**\
	   The `\AntispamBee\Admin\DashboardWidgets` class handles displaying the spam counts in the dashboard widget, if it is enabled.
	3. **Settings page**\
	   The `\AntispamBee\Admin\SettingsPage` class adds the settings page.
	4. **Spam reason in comment column**\
	   If the option to save the spam reason is enabled for at least one reaction type, the `\AntispamBee\Admin\CommentsColumns` class handles that.
