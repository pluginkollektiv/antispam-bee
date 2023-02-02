# Antispam Bee #
* Contributors:      pluginkollektiv, websupporter, schlessera, zodiac1978, swissspidy, krafit, kau-boy, florianbrinkmann, pfefferle
* Tags:              anti-spam, antispam, block spam, comment, comments, comment spam, pingback, spam, spam filter, trackback, GDPR
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW
* Requires at least: 4.5
* Tested up to:      6.1
* Requires PHP:      5.2
* Stable tag:        2.11.2
* License:           GPLv2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Antispam plugin with a sophisticated tool set for effective day to day comment and trackback spam-fighting. Build with data protection and privacy in mind.

## Description ##
Say Goodbye to comment spam on your WordPress blog or website. *Antispam Bee* blocks spam comments and trackbacks effectively, without captchas and without sending personal information to third party services. It is free of charge, ad-free and 100% GDPR compliant.

### Feature/Settings Overview ###
* Trust approved commenters.
* Trust commenters with a Gravatar.
* Consider the comment time.
* Allow comments only in a certain language.
* Block or allow commenters from certain countries.
* Treat BBCode links as spam.
* Validate the IP address of commenters.
* Use regular expressions.
* Search local spam database for commenters previously marked as spammers.
* Notify admins by e-mail about incoming spam.
* Delete existing spam after n days.
* Limit approval to comments/pings (will delete other comment types).
* Select spam indicators to send comments to deletion directly.
* Optionally exclude trackbacks and pingbacks from spam detection.
* Optionally spam-check comment forms on archive pages.
* Display spam statistics on the dashboard, including daily updates of spam detection rate and a total of blocked spam comments.

### Support ###
* Community support via the [support forums on wordpress.org](https://wordpress.org/support/plugin/antispam-bee)
* Read [the documentation](https://antispambee.pluginkollektiv.org/documentation/)
* We don’t handle support via e-mail, Twitter, GitHub issues etc.

### Contribute ###
* Active development of this plugin is handled [on GitHub](https://github.com/pluginkollektiv/antispam-bee).
* Pull requests for documented bugs are highly appreciated.
* If you think you’ve found a bug (e.g. you’re experiencing unexpected behavior), please post at the [support forums](https://wordpress.org/support/plugin/antispam-bee) first.
* If you want to help us translate this plugin you can do so [on WordPress Translate](https://translate.wordpress.org/projects/wp-plugins/antispam-bee).

### Credits ###
* Author: [Sergej Müller](https://sergejmueller.github.io/)
* Maintainers: [pluginkollektiv](https://pluginkollektiv.org)

## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### Requirements ###
* PHP 5.2.4 or greater
* WordPress 4.5 or greater

### Settings ###
After you have activated *Antispam Bee* the plugin will block spam comments out of the box. However, you may want to visit *Settings → Antispam Bee* to configure your custom set of anti-spam options that works best for your site.

## Frequently Asked Questions ##

### Does Antispam Bee prevents spam registrations or protects form plugins? ###
Antispam Bee works best with default WordPress comments. It does not help to protect form plugins and does not prevent spam registrations. Hopefully we can provide better hooks for third party plugins to use Antispam Bee to fill this gap in the forthcoming new major version.

### Does Antispam Bee work with Jetpack, wpDiscuz, Disqus Comments and similar comment plugins?
Antispam Bee works best with default WordPress comments. It is not compatible with Jetpack, wpDiscuz or Disqus Comments as those plugins load a new comment form within an iframe. Thus Antispam Bee can not access the comment form directly.

### Does Antispam Bee work with AJAX comment plugins or similar theme features?
Whether Antispam Bee works with a comment form submitted via AJAX depends on how the AJAX request is made. If the request goes to the file that usually also receives the comments, Antispam Bee could work with it out of the box (the [WP Ajaxify Comments](https://wordpress.org/plugins/wp-ajaxify-comments/) plugin does this, for example). 

If the comments are sent to the `admin-ajax.php`, the `antispam_bee_disallow_ajax_calls` filter must be used to run ASB for requests to that file as well. If the script does not send all form data to the file, but only some selected ones, further customization is probably necessary, as [exemplified in this post by Torsten Landsiedel](https://torstenlandsiedel.de/2020/10/04/ajaxifizierte-kommentare-und-antispam-bee/) (in German).

### Does Antispam Bee store any private user data, and is it compliant with GDPR? ###
Antispam Bee is developed in Europe. You might have heard we can be a bit nitpicky over here when it comes to privacy. The plugin does not save private user data and is 100% compliant with GDPR.

### Will I have to edit any theme templates to get Antispam Bee to work? ###
No, the plugin works as is. You may want to configure your favorite settings, though.

### Does Antispam Bee work with shortened IPs? ###
Generally yes. However, commissioning the Antispam Bee plugin for canceled or shortened IP addresses in comment metadata is not recommended. Because the name and the e-mail address of the comments are not unique, an IP address is the only reliable measure. The more complete the stored IP addresses, the more reliable the assignment or detection of spam.

### How can I submit undetected spam? ###
If the antispam plugin has passed some spam comments, these comments can be reported for analysis. A [Google table](http://goo.gl/forms/ITzVHXkLVL) was created for this purpose.

### Antispam Bee with Varnish? ###
If WordPress is operated with Apache + Varnish, the actual IP address of the visitors does not appear in WordPress. Accordingly the Antispam-Plugin lacks the base for the correct functionality. An adaptation in the Varnish configuration file /etc/varnish/default.vcl provides a remedy and forwards the original (not from Apache) IP address in the HTTP header X-Forwarded-For:

> if (req.restarts == 0) {
>     set req.http.X-Forwarded-For = client.ip;
> }

### Are there some paid services or limitations? ###
No, Antispam Bee is free forever, for both private and commercial projects. You can use it on as many sites as you want. There is no limitation to the number of sites you use the plugin on.

A complete documentation is available on [pluginkollektiv.org](https://antispambee.pluginkollektiv.org/documentation/).

## Changelog ##

### 2.11.2 ###
  * Tweak: remove superfluous translations
  * Tweak: make FAQ link an anchor link
  * Fix: spam counter no longer raises a warning with PHP 8.1 if no spam is present yet
  * Fix: spam reasons are now localized correctly
  * Fix: Translations were loaded twice on some admin pages
  * Maintenance: Tested up to WordPress 6.1

### 2.11.1 ###
  * Tweak: remove superfluous type attribute from inline script tag
  * Maintenance: Tested up to WordPress 6.0

### 2.11.0 ###
  * Fix: Allow empty comments if `allow_empty_comment` is set to true
  * Fix: Add `aria-label` to work around bug in a11y testing tools
  * Fix: Change priority for `comment_form_field_comment` from 10 to 99
  * Tweak: Updated some FAQ entries
  * Tweak: Updated build tooling

### 2.10.0 ###
  * Fix: Switch from ip2country.info to iplocate.io for country check
  * Enhancement: Use filter to add the honeypot field instead of output buffering for new installations and added option to switch between the both ways
  * Tweak: Added comment user agent to regex pattern check
  * Tweak: Make the ping detection filterable to support new comment types
  * Tweak: Updated internal documentation links
  * Tweak: Several updates and optimizations in the testing process
  * Tweak: Adjust color palette to recent WP version
  * Tweak: Adjust wording in variables and option names
  * Readme: Add new contributor and clean up unused code


### 2.9.4 ###
  * Enhancement: Add filter to allow ajax calls
  * Tweak: Better wording for BBCode feature in plugin description
  * Tweak: Better screenshots in the plugin directory
  * Maintenance: Tested up to WordPress 5.7

### 2.9.3 ###
  * Fixed: Compatibility with WordPress 5.5
  * Fixed: Undefined index on spam list page
  * Tweak: Better wording on settings page
  * Tweak: AMP compatibility
  * Tweak: Protect CSS from overwrite through bad themes

### 2.9.2 ###
  * Fix: Delete comment meta for deleted old spam. For the cleanup of older orphaned comment meta we suggest the usage of [WP Sweep](https://wordpress.org/plugins/wp-sweep/)
  * Fix: Statistic in dashboard showed wrong value
  * Tweak: Change autocomplete attribute to "new-password"
  * Tweak: Autoptimize compatibility improved
  * Tweak: Renamed blacklist/whitelist to a better phrase
  * Tweak: Added new pattern
  * Tweak: UI and text optimizations
  * Tweak: Better compatibility with some server configurations
  * Tweak: Make spam reason sortable and filterable
  * Tweak: Add spam reason for manually marked spam
  * Maintenance: Deleted unused code
  * Maintenance: Removed Fake IP check (unreliable and producing false positives)
  * Maintenance: Fix some coding standard issues
  * Maintenance: Tested up to WordPress 5.4
  * Maintenance: Tested up to PHP 7.4

### 2.9.1 ###
  * Improved backend accessibility
  * Prefilled comment textareas do now work with the honeypot
  * Compatible with the AMP plugin (https://wordpress.org/plugins/amp/)
  * Improved dashboard tooltips
  * Improvements for the language detection API
  * Scalable IP look up for local spam database

### 2.9.0 ###
  * Introduction of coding standards.
  * Switch to franc language detection API for the language check.
  * Do not longer overwrite the IP address WordPress saves with the comment by using `pre_comment_user_ip`.
  * Do not show "Trust commenters with a Gravatar" if the "Show Gravatar" option is not set.
  * Skip the checks, when I ping myself.
  * Fixes some wrong usages of the translation functions.
  * Use the regular expressions check also for trackbacks.
  * Add option to delete Antispam Bee related data when plugin gets deleted via the admin interface.
  * Save a hashed + salted IP for every comment
  * New check for incoming trackbacks.
  * Introduction of behat tests.
  * Updates the used JavaScript library for the statistics widget.
  * Bugfix in the "Comment form used outside of posts" option.

### 2.8.1 ###
  * PHP 5.3 compatibility
  * Bugfix where a spam trackback produced a fatal error
  * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/8?closed=1

### 2.8.0 ###
   * Removed stopforumspam.com to avoid potential GDPR violation
   * Improves IP handling to comply with GDPR
   * Improves PHP7.2 compatibility
   * Fixes small bug on mobile views
   * Allow more than one language in language check
   * Minor interface improvements
   * Remove old russian and Dutch translation files
   * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/4?closed=1

### 2.7.1 ###
   * Fixes an incompatibility with Chrome autofill
   * Fixes some incompatibilities with other plugins/themes where the comment field was left empty
   * Support for RTL
   * Solve some translation/language issues
   * A new filter to add languages to the language check
   * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/6?closed=1

### 2.7.0 ###
   * Country check is back again (thanks to Sergej Müller for his amazing work and the service page)
   * Improved Honeypot
   * Language check through Google Translate API is back again (thanks to Simon Kraft of https://moenus.net/ for offering to cover the costs)
   * More default Regexes
   * Unit Test Framework
   * Accessibility and GUI improvements
   * An [english documentation](https://github.com/pluginkollektiv/antispam-bee/wiki) is now available, too. Some corrections in the german documentation.
   * Some bugfixes - Among other things for WPML compatibility
   * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/3?closed=1

### 2.6.9 ###
   * Updates donation links throughout the plugin
   * Fixes an error were JavaScript on the dashboard was erroneously being enqueued
   * Ensures compatibility with the latest WordPress version

### 2.6.8 ###
   * added a POT file
   * updated German translation, added formal version
   * updated plugin text domain to include a dash instead of an underscore
   * updated, translated + formatted README.md
   * updated expired link URLs in plugin and languages files
   * updated [plugin authors](https://pluginkollektiv.org/hello-world/)

### 2.6.7 ###
   * Removal of functions *Block comments from specific countries* and *Allow comments only in certain language* for financial reasons - [more information](https://antispambee.pluginkollektiv.org/news/2015/removal-of-allow-comments-only-in-certain-language/)

### 2.6.6 ###
   * Switch to the official Google Translation API
   * *Release time investment (Development & QA): 2.5 h*

### 2.6.5 ###
   * Fix: Return parameters on `dashboard_glance_items` callback / thx [@toscho](https://twitter.com/toscho)
   * New function: Trust commenters with a Gravatar / thx [@glueckpress](https://twitter.com/glueckpress)
   * Additional plausibility checks and filters
   * *Release time investment (Development & QA): 12 h*

### 2.6.4 ###
   * Consideration of the comment time (Spam if a comment was written in less than 5 seconds) - [more information](https://antispambee.pluginkollektiv.org/news/2014/antispam-bee-2-6-4/)
   * *Release time investment (Development & QA): 6.25 h*

### 2.6.3 ###
   * Sorting for the Antispam Bee column in the spam comments overview
   * Code refactoring around the use of REQUEST_URI
   * *Release time investment (Development & QA): 2.75 h*

### 2.6.2 ###
   * Improving detection of fake IPs
   * *Release time investment (Development & QA): 11 h*

### 2.6.1 ###
   * Code refactoring of options management
   * Support for `HTTP_FORWARDED_FOR` header
   * *Release time investment (Development & QA): 8.5 h*

### 2.6.0 ###
   * Optimizations for WordPress 3.8
   * Clear invalid UTF-8 characters in comment fields
   * Spam reason as a column in the table with spam comments

For the complete changelog, check out our [GitHub repository](https://github.com/pluginkollektiv/antispam-bee).

== Upgrade Notice ==

= 2.8.0 =
This update makes sure your spam check is GDPR compliant, no matter the options you choose. Please make sure to update before May 25th!

## Screenshots ##
1. Block or allow comments from specific countries.
2. Allow comments only in certain languages.
3. Add useful spam stats to your dashboard.
4. Tailor WordPress' spam management to your workflow.
