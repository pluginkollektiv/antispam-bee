# Project Structure

Comparing to version 2 of Antispam Bee, the project structure has changed a lot. We now do not have one large file that contains all spam detection, but various classes doing that.

The most important part of ASB 3 are rules and post processors. Both, rules and post processors, can be controllable via the options page or work invisible in the background without registering a control.

## Detecting spam with »Rules«

Responsible for detecting spam reactions (reactions are things like comments or linkbacks) are »Rules« that you can find in the `src/Rules` directory.

## Handling spam with »Post processors«

If spam is detected, so-called »Post processors« jump in to handle it. They can be found in `src/PostProcessors`.

## Program flow

1. Entry point for the plugin is, of course, the `antispam_bee.php`. There we check a few requirements like PHP version and PHP extensions. If everything looks good, we go to step 2.
2. Meeting all requirements in `antispam_bee.php`, the `src/load.php` file is loaded. Besides registering hooks for doing things on activation/deactivation/uninstall/update, this is the place where the modules of ASB are loaded.
In the `init` method is an array of classes, over which we loop after in the next step and call the `init` method on all classes where it exists.
The `init` methods in the module classes contain `add_action` and `add_filter` calls to hook into WordPress and do their thing. So, what happens next depends on the kind of request that is done.
      1. **A reaction (comment/linkback/…) coming in:**
      Handling comment and linkback requests happens in the classes `\AntispamBee\Handlers\Comment` and `\AntispamBee\Handlers\Linkback`. If you look in the `src/load.php` you can see both as part of the modules array.
      The classes extend the abstract `\AntispamBee\Handlers\Reaction` class that comes with a basic `init` method, being good enough for the linkback reactions.
      In the `process` methods of the classes you can see the process of applying rules to a reaction and, if the rules come to the result it is spam, how the post processors are applied.
         1. To apply all rules that are active for comments to a reaction, all you have to do is:
            ```php
            $rules   = new \AntispamBee\Handlers\Rules( 'comment' );
            $is_spam = $rules->apply( $reaction );
            ```
            Where `$reaction` is an array of comment data. The `apply` method returns a boolean indicating if the reaction is spam or not.
            g
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
            * `$item['content_type']` holds the content type that is passed as the first parameter to the `apply` method.
            * If `$item['asb_marked_as_delete']` is set, one or more post processors were applied that indicate that the comment should not be stored.
      2. **The user visiting the dashboard:**
      The `\AntispamBee\Admin\DashboardWidgets` class handles displaying the spam counts in the dashboard widget, if it is enabled.
      3. **Settings page**
      The `\AntispamBee\Admin\SettingsPage` class adds the settings page.
      4. **Spam reason in comment column**
      If the option to save the spam reason is enabled for at least one reaction type, the `\AntispamBee\Admin\CommentsColumns` class handles that.

## The other files

- In the `src/GeneralOptions` folder are the classes that handle the options in the »General« tab.
-
