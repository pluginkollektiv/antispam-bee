# Antispam Bee #
* Contributors:      pluginkollektiv
* Tags:              anti-spam, antispam, block spam, comment, comments, comment spam, pingback, spam, spam filter, trackback, GDPR
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW
* Requires at least: 4.5
* Tested up to:      5.0
* Stable tag:        2.9.1
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
* Treat BBCode as spam.
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
* Read [the documentation](https://github.com/pluginkollektiv/antispam-bee/wiki/en-Documentation)
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
* WordPress 3.8 or greater

### Settings ###
After you have activated *Antispam Bee* the plugin will block spam comments out of the box. However, you may want to visit *Settings → Antispam Bee* to configure your custom set of anti-spam options that works best for your site.

## Frequently Asked Questions ##

### Does Antispam Bee work with Jetpack, Disqus Comments and other comment plugins? ###
Antispam Bee works best with default WordPress comments. It is not compatible with Jetpack or Disqus Comments as those plugins load the comment form within an iframe. Thus Antispam Bee can not access the comment form directly.
It also won’t work with any AJAX-powered comment forms.

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

```
if (req.restarts == 0) {
    set req.http.X-Forwarded-For = client.ip;
}
```

### Are there some paid services or limitations? ###
No, Antispam Bee is free forever, for both private and commercial projects. You can use it on as many sites as you want. There is no limitation to the number of sites you use the plugin on.

A complete documentation is available in the [GitHub repository Wiki](https://github.com/pluginkollektiv/antispam-bee/wiki).

## Changelog ##

### 2.9.1 ###
  * Improved backend accessibility
  * Prefilled comment textareas do now work with the honeypot
  * AMP compatibility
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
   * updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)

### 2.6.7 ###
   * Removal of functions *Block comments from specific countries* and *Allow comments only in certain language* for financial reasons - [more information](https://plus.google.com/u/0/+SergejMüller/posts/ZyquhoYjUyF) (only german)

### 2.6.6 ###
   * Switch to the official Google Translation API - [more information](https://plus.google.com/u/0/+SergejMüller/posts/ZyquhoYjUyF) (only german)
   * *Release time investment (Development & QA): 2.5 h*

### 2.6.5 ###
   * Fix: Return parameters on `dashboard_glance_items` callback / thx [@toscho](https://twitter.com/toscho)
   * New function: Trust commenters with a Gravatar / thx [@glueckpress](https://twitter.com/glueckpress)
   * Additional plausibility checks and filters
   * *Release time investment (Development & QA): 12 h*

### 2.6.4 ###
   * Consideration of the comment time (Spam if a comment was written in less than 5 seconds) - [more information on Google+](https://plus.google.com/+SergejMüller/posts/73EbP6F1BgC) (only german)
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
1. Antispam Bee settings
