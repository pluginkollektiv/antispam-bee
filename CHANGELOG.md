## Changelog ##

### 2.6.9 ###
* **English**
   * Updates donation links throughout the plugin
   * Fixes an error were JavaScript on the dashboard was erroneously being enqueued
   * Ensures compatibility with the latest WordPress version
* **Deutsch**

### 2.6.8 ###
* **English**
   * added a POT file
   * updated German translation, added formal version
   * updated plugin text domain to include a dash instead of an underscore
   * updated, translated + formatted README.md
   * updated expired link URLs in plugin and languages files
   * updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)
* **Deutsch**

### 2.6.7 ###
* **English**
   * Removal of functions *Block comments from specific countries* and *Allow comments only in certain language* for financial reasons
* **Deutsch**
   * Entfernung der Funktionen *Kommentare nur in einer Sprache zulassen* und *Bestimmte Länder blockieren bzw. erlauben* aus finanziellen Gründen - [Hintergrund-Informationen](https://plus.google.com/u/0/+SergejMüller/posts/ZyquhoYjUyF)

### 2.6.6 ###
* **English**
    * Switch to the official Google Translation API
    * *Release time investment (Development & QA): 2.5 h*
* **Deutsch**
    * (Testweise) Umstellung auf die offizielle Google Translation API - [Hintergrund-Informationen](https://plus.google.com/u/0/+SergejMüller/posts/ZyquhoYjUyF)
    * *Release-Zeitaufwand (Development & QA): 2,5 Stunden*

### 2.6.5 ###
* **English**
   * Fix: Return parameters on `dashboard_glance_items` callback / thx [@toscho](https://twitter.com/toscho)
   * New function: Trust commenters with a Gravatar / thx [@glueckpress](https://twitter.com/glueckpress)
   * Additional plausibility checks and filters
   * *Release time investment (Development & QA): 12 h*
* **Deutsch**
   * Fix: Parameter-Rückgabe bei `dashboard_glance_items` / thx [@toscho](https://twitter.com/toscho)
   * Neue Funktion: [Kommentatoren mit Gravatar vertrauen](https://github.com/pluginkollektiv/antispam-bee/wiki/Dokumentation) / thx [@glueckpress](https://twitter.com/glueckpress)
   * Zusätzliche Plausibilitätsprüfungen und Filter
   * *Release-Zeitaufwand (Development & QA): 12 Stunden*

### 2.6.4 ###
* **English**
   * Consideration of the comment time (Spam if a comment was written in less than 5 seconds)
   * *Release time investment (Development & QA): 6.25 h*
* **Deutsch**
   * Berücksichtigung der Kommentarzeit (Spam, wenn ein Kommentar in unter 5 Sekunden verfasst) - [Hintergrund-Informationen](https://plus.google.com/+SergejMüller/posts/73EbP6F1BgC)
   * *Release-Zeitaufwand (Development & QA): 6,25 Stunden*

### 2.6.3 ###
* **English**
    * Sorting for the Antispam Bee column in the spam comments overview
    * Code refactoring around the use of REQUEST_URI
    * *Release time investment (Development & QA): 2.75 h*
* **Deutsch**
    * Sortierung für die Antispam Bee Spalte in der Spam-Übersicht
    * Code-Refactoring rund um die Nutzung von REQUEST_URI
    * *Release-Zeitaufwand (Development & QA): 2,75 Stunden*

### 2.6.2 ###
* **English**
    * Improving detection of fake IPs
    * *Release time investment (Development & QA): 11 h*
* **Deutsch**
    * Überarbeitung der Erkennung von gefälschten IPs
    * *Release-Zeitaufwand (Development & QA): 11 Stunden*

### 2.6.1 ###
* **English**
    * Code refactoring of options management
    * Support for `HTTP_FORWARDED_FOR` header
    * *Release time investment (Development & QA): 8.5 h*
* **Deutsch**
    * Überarbeitung der Optionen-Verwaltung
    * Berücksichtigung der Header `HTTP_FORWARDED_FOR`
    * *Release-Zeitaufwand (Development & QA): 8,5 Stunden*

### 2.6.0 ###
* **English**
   * Optimizations for WordPress 3.8
   * Clear invalid UTF-8 characters in comment fields
   * Spam reason as a column in the table with spam comments
* **Deutsch**
   * Optimierungen für WordPress 3.8
   * Zusatzprüfung auf Nicht-UTF-8-Zeichen in Kommentardaten
   * Spamgrund als Spalte in der Übersicht mit Spamkommentaren

### 2.5.9 ###
* **English**
   * Dashboard widget changes to work with [Statify](http://statify.de)
* **Deutsch**
   * Anpassung des Dashboard-Skriptes für die Zusammenarbeit mit [Statify](http://statify.de)

### 2.5.8 ###
* **English**
   * Switch from TornevallDNSBL to [Stop Forum Spam](http://www.stopforumspam.com)
   * New JS library for the Antispam Bee dashboard chart
* **Deutsch**
   * Umstellung von TornevallDNSBL zu [Stop Forum Spam](http://www.stopforumspam.com)
   * Neue JS-Bibliothek für das Dashboard-Widget
   * [Mehr Informationen auf Google+](https://plus.google.com/110569673423509816572/posts/VCFr3fDAYDs)

### 2.5.7 ###
* **English**
   * Optional logfile with spam entries e.g. for [Fail2Ban](https://gist.github.com/sergejmueller/5622883)
   * Filter `antispam_bee_notification_subject` for a custom subject in notifications
* **Deutsch**
   * Optionale Spam-Logdatei z.B. für [Fail2Ban](https://github.com/sergejmueller/sergejmueller.github.io/wiki/Fail2Ban:-IP-Blacklist)
   * Filter `antispam_bee_notification_subject` für eigenen Betreff in Benachrichtigungen
   * Detaillierte Informationen zum Update auf [Google+](https://plus.google.com/110569673423509816572/posts/iCfip2ggYt9)

### 2.5.6 ###
* **English**
   * Added new detection/patterns for spam comments
* **Deutsch**
   * Neue Erkennungsmuster für Spam hinzugefügt / [Google+](https://plus.google.com/110569673423509816572/posts/9BSURheN3as)

### 2.5.5 ###
* **English**
   * Detection and filtering of spam comments that try to exploit the latest [W3 Total Cache and WP Super Cache Vulnerability](http://blog.sucuri.net/2013/05/w3-total-cache-and-wp-super-cache-vulnerability-being-targeted-in-the-wild.html).
* **Deutsch**
   * Erkennung und Ausfilterung von Spam-Kommentaren, die versuchen, [Sicherheitslücken von W3 Total Cache und WP Super Cache](http://blog.sucuri.net/2013/05/w3-total-cache-and-wp-super-cache-vulnerability-being-targeted-in-the-wild.html) auszunutzen. [Ausführlicher auf Google+](https://plus.google.com/110569673423509816572/posts/afWWQbUh4at).

### 2.5.4 ###
* **English**
   * 
* **Deutsch**
   * Jubiläumsausgabe: [Details zum Update](https://plus.google.com/110569673423509816572/posts/3dq9Re5vTY5)
   * Neues Maskottchen für Antispam Bee
   * Erweiterte Prüfung eingehender Kommentare in lokaler Blog-Spamdatenbank auf IP, URL und E-Mail-Adresse

### 2.5.3 ###
* **English**
   * 
* **Deutsch**
   * Optimierung des Regulären Ausdrucks

### 2.5.2 ###
* **English**
   * 
* **Deutsch**
   * Neu: [Reguläre Ausdrücke anwenden](https://github.com/pluginkollektiv/antispam-bee/wiki/Dokumentation) mit vordefinierten und eigenen Erkennungsmustern
   * Änderung der Filter-Reihenfolge
   * Verbesserungen an der Sprachdatei
   * [Hintergrundinformationen zum Update](https://plus.google.com/110569673423509816572/posts/CwtbSoMkGrT)

### 2.5.1 ###
* **English**
   * 
* **Deutsch**
   * [BBCode im Kommentar als Spamgrund](https://github.com/pluginkollektiv/antispam-bee/wiki/Dokumentation)
   * IP-Anonymisierung bei der Länderprüfung
   * [Mehr Transparenz](https://plus.google.com/110569673423509816572/posts/ZMU6RfyRK29) durch hinzugefügte Datenschutzhinweise
   * PHP 5.2.4 als Voraussetzung (ist zugleich die Voraussetzung für WP 3.4)

### 2.5.0 ###
* **English**
   * Edition 2012
* **Deutsch**
   * [Edition 2012](https://plus.google.com/110569673423509816572/posts/6JUC6PHXd6A)

### 2.4.6 ###
* **English**
   * 
* **Deutsch**
   * Russische Übersetzung
   * Veränderung der Secret-Zeichenfolge

### 2.4.5 ###
* **English**
   * 
* **Deutsch**
   * Überarbeitetes Layout der Einstellungen
   * Streichung von Project Honey Pot
   * TornevallNET als neuer DNSBL-Dienst
   * WordPress 3.4 als Mindestvoraussetzung
   * WordPress 3.5 Unterstützung
   * Neufassung des Online-Handbuchs

### 2.4.4 ###
* **English**
   * 
* **Deutsch**
   * Technical and visual support for WordPress 3.5
   * Modification of the file structure: from `xyz.dev.css` to `xyz.min.css`
   * Retina screenshot

### 2.4.3 ###
* **English**
   * Check for basic requirements
   * Remove the sidebar plugin icon
   * Set the Google API calls to SSL
   * Compatibility with WordPress 3.4
   * Add retina plugin icon on options
   * Depending on WordPress settings: anonymous comments allowed
* **Deutsch**
   * 

### 2.4.2 ###
* **English**
   * New geo ip location service (without the api key)
   * Code cleanup: Replacement of `@` characters by a function
   * JS-Fallback for missing jQuery UI
* **Deutsch**
   * 

### 2.4.1 ###
* **English**
   * Add russian translation
   * Fix for the textarea replace
   * Detect and hide admin notices
* **Deutsch**
   * 

### 2.4 ###
* **English**
   * Support for IPv6
   * Source code revision
   * Delete spam by reason
   * Changing the user interface
   * Requirements: PHP 5.1.2 and WordPress 3.3
* **Deutsch**
   * 

### 2.3 ###
* **English**
   * Xmas Edition
* **Deutsch**
   * 

### 2.2 ###
* **English**
   * Interactive Dashboard Stats
* **Deutsch**
   * 

### 2.1 ###
* **English**
   * Remove Google Translate API support
* **Deutsch**
   * 

### 2.0 ###
* **English**
   * Allow comments only in certain language (English/German)
   * Consider comments which are already marked as spam
   * Dashboard Stats: Change from canvas to image format
   * System requirements: WordPress 2.8
   * Removal of the migration script
   * Increase plugin security
* **Deutsch**
   * 

### 1.9 ###
* **English**
   * Dashboard History Stats (HTML5 Canvas)
* **Deutsch**
   * 

### 1.8 ###
* **English**
   * Support for the new IPInfoDB API (including API Key)
* **Deutsch**
   * 

### 1.7 ###
* **English**
   * Black and whitelisting for specific countries
   * "Project Honey Pot" as a optional spammer source
   * Spam reason in the notification email
   * Visual refresh of the notification email
   * Advanced GUI changes + Fold-out options
* **Deutsch**
   * 

### 1.6 ###
* **English**
   * Support for WordPress 3.0
   * System requirements: WordPress 2.7
   * Code optimization
* **Deutsch**
   * 

### 1.5 ###
* **English**
   * Compatibility with WPtouch
   * Add support for do_action
   * Translation to Portuguese of Brazil
* **Deutsch**
   * 

### 1.4 ###
* **English**
   * Enable stricter inspection for incomming comments
   * Do not check if the author has already commented and approved
* **Deutsch**
   * 

### 1.3 ###
* **English**
   * New code structure
   * Email notifications about new spam comments
   * Novel Algorithm: Advanced spam checking
* **Deutsch**
   * 

### 1.2 ###
* **English**
   * Antispam Bee spam counter on dashboard
* **Deutsch**
   * 

### 1.1 ###
* **English**
   * Adds support for WordPress new changelog readme.txt standard
   * Various changes for more speed, usability and security
* **Deutsch**
   * 

### 1.0 ###
* **English**
   * Adds WordPress 2.8 support
* **Deutsch**
   * 

### 0.9 ###
* **English**
   * Mark as spam only comments or only pings
* **Deutsch**
   * 

### 0.8 ###
* **English**
   * Optical adjustments of the settings page
   * Translation for Simplified Chinese, Spanish and Catalan
* **Deutsch**
   * 

### 0.7 ###
* **English**
   * Spam folder cleanup after X days
   * Optional hide the &quot;MARKED AS SPAM&quot; note
   * Language support for Italian and Turkish
* **Deutsch**
   * 

### 0.6 ###
* **English**
   * Language support for English, German, Russian
* **Deutsch**
   * 

### 0.5 ###
* **English**
   * Workaround for empty comments
* **Deutsch**
   * 

### 0.4 ###
* **English**
   * Option for trackback and pingback protection
* **Deutsch**
   * 

### 0.3 ###
* **English**
   * Trackback and Pingback spam protection
* **Deutsch**
   * 
