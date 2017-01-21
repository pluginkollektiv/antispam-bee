# Antispam Bee #
* Contributors:      pluginkollektiv
* Tags:              anti-spam, antispam, block spam, comment, comments, comment spam, pingback, prevention, protect, protection, spam, spam filter, trackback
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8CH5FPR88QYML
* Requires at least: 3.8
* Tested up to:      4.7
* Stable tag:        2.6.9
* License:           GPLv2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Easy and extremely productive spam-fighting plugin with many sophisticated solutions. Includes protection against trackback spam and privacy hints.

## Description ##
Say Goodbye to comment spam on your WordPress blog or website. *Antispam Bee* blocks spam comments and trackbacks effectively and without captchas. It is free of charge, ad-free and compliant with European data privacy standards.

### Feature/Settings Overview ###
* Trust approved commenters.
* Trust commenters with a Gravatar.
* Consider the comment time.
* Treat BBCode as spam.
* Validate the IP address of commenters.
* Use regular expressions.
* Search local spam database for commenters previously marked as spammers.
* Match against a public anti-spam database.
* Notify admins by e-mail about incoming spam.
* Delete existing spam after n days.
* Limit approval to comments/pings (will delete other comment types).
* Select spam indicators to send comments to deletion directly.
* Optionally exclude trackbacks and pingbacks from spam detection.
* Optionally spam-check comment forms on archive pages.
* Display spam statistics on the dashboard, including daily updates of spam detection rate and a total of blocked spam comments.

### Support ###
* Community support via the [support forums on wordpress.org](https://wordpress.org/support/plugin/antispam-bee)
* We don’t handle support via e-mail, Twitter, GitHub issues etc.

### Contribute ###
* Active development of this plugin is handled [on GitHub](https://github.com/pluginkollektiv/antispam-bee).
* Pull requests for documented bugs are highly appreciated.
* If you think you’ve found a bug (e.g. you’re experiencing unexpected behavior), please post at the [support forums](https://wordpress.org/support/plugin/antispam-bee) first.
* If you want to help us translate this plugin you can do so [on WordPress Translate](https://translate.wordpress.org/projects/wp-plugins/antispam-bee).

### Credits ###
* Author: [Sergej Müller](https://sergejmueller.github.io/)
* Maintainers: [pluginkollektiv](http://pluginkollektiv.org)

## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### Requirements ###
* PHP 5.2.4 or greater
* WordPress 3.8 or greater

### Settings ###
After you have activated *Antispam Bee* the plugin will block spam comments out of the box. However, you may want to visit *Settings → Antispam Bee* to configure your custom set of anti-spam options that works best for your site.

### Privacy Notice ###
On sites operating from within the EU the option *Use a public antispam database* should not be activated for privacy reasons. When that option has been activated, *Antispam Bee* will match full IP addresses from comments against a public spam database. Technically it is not possible to encrypt those IPs, because spam databases only store and operate with complete, unencrypted IP addresses.

## Frequently Asked Questions ##

### Does Antispam Bee work with Jetpack, Disqus Comments and other comment plugins? ###
Antispam Bee works best with default WordPress comments. It is not compatible with Jetpack or Disqus Comments as those plugins load the comment form within an iframe. Thus Antispam Bee can not access the comment form directly.
It also won’t work with any AJAX-powered comment forms.

### On how many web sites or blogs can I use Antispam Bee? ###
On as many as you wish. There is no limitation to the number of sites you use the plugin on.

### Do I have to register for any sort of paid service if my site gets a lot of comment spam? ###
No, Antispam Bee is free forever, for both private and commercial projects.

### Does Antispam Bee store any private user data, IP addresses or the like? ###
Nope. Antispam Bee is developed in Germany and Switzerland. You might have heard we can be a bit nitpicky over here when it comes to privacy.

### Will I have to edit any theme templates to get Antispam Bee to work? ###
No, the plugin works as is. You may want to configure your favorite settings, though.

A complete documentation is available in the [GitHub repository Wiki](https://github.com/pluginkollektiv/antispam-bee/wiki).

## Changelog ##

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

## Upgrade Notice ##

### 2.6.9 ###

This is mainly a maintenance release ensuring compatibility with the latest version of WordPress. Expect bigger changes in 2.7 soon!

## Screenshots ##
1. Antispam Bee settings
