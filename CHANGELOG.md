## Changelog ##

### 2.11.2 ###
* **English**
  * Tweak: remove superfluous translations
  * Tweak: make FAQ link an anchor link
  * Fix: spam counter no longer raises a warning with PHP 8.1 if no spam is present yet
  * Fix: spam reasons are now localized correctly
  * Fix: Translations were loaded twice on some admin pages
  * Maintenance: Tested up to WordPress 6.1

* **Deutsch**
  * Tweak: Überflüssige Übersetzungen entfernt
  * Tweak: Link zu den FAQ ist jetzt ein Anker-Link
  * Fix: Der Spam-Zähler erzeugt mit PHP 8.1 keine Warnung mehr, wenn noch kein Spam vorhanden ist
  * Fix: Spam-Gründe werden nun korrekt übersetzt
  * Fix: Übersetzungen wurden auf einzelnen Adminseiten doppelt geladen
  * Wartung: Getestet mit WordPress 6.1

### 2.11.1 ###
* **English**
  * Tweak: remove superfluous type attribute from inline script tag
  * Maintenance: Tested up to WordPress 6.0

* **Deutsch**
  * Tweak: Überflüssiges type-Attribut von script-Tag entfernt
  * Wartung: Getestet mit WordPress 6.0

### 2.11.0 ###
* **English**
  * Fix: Allow empty comments if `allow_empty_comment` is set to true
  * Fix: Add `aria-label` to work around bug in a11y testing tools
  * Fix: Change priority for `comment_form_field_comment` from 10 to 99
  * Tweak: Updated some FAQ entries
  * Tweak: Updated build tooling

* **Deutsch**
  * Fix: Leere Kommentare erlauben, wenn der Filter `allow_empty_comment` gesetzt ist
  * Fix: Ein `aria-label` hinzugefügt, um einen bekannten Fehler bei Tests zu umgehen
  * Fix: Änderung der Priorität vom Filter `comment_form_field_comment` von 10 auf 99
  * Tweak: Aktualisierungen in der FAQ
  * Tweak: Optimierungen am Build-Prozess

### 2.10.0 ###
* **English**
  * Fix: Switch from ip2country.info to iplocate.io for country check
  * Enhancement: Use filter to add the honeypot field instead of output buffering for new installations and added option to switch between the both ways
  * Tweak: Added comment user agent to regex pattern check
  * Tweak: Make the ping detection filterable to support new comment types
  * Tweak: Updated internal documentation links
  * Tweak: Several updates and optimizations in the testing process
  * Tweak: Adjust color palette to recent WP version
  * Tweak: Adjust wording in variables and option names
  * Readme: Add new contributor and clean up unused code

* **Deutsch**
  * Fix: Wechsel von ip2country.info zu iplocate.io für die Länderprüfung
  * Verbesserung: Bei neuen Installationen wird ein Filter zum Hinzufügen des Honeypot-Felds genutzt statt Output-Buffering. Es wurde eine Option hinzugefügt, zwischen den beiden Wegen zu wechseln
  * Tweak: Kommentar User-Agent zu Regex-Pattern hinzugefügt
  * Tweak: Die Ping-Erkennung ist jetzt filterbar, um neue Kommentartypen zu unterstützen
  * Tweak: Aktualisierte Links zur internen Dokumentation
  * Tweak: Verschiedene Aktualisierungen und Optimierungen im Testprozess
  * Tweak: Farbpalette an aktuelle WP-Version anpassen
  * Tweak: Wortlaut in Variablen und Optionsnamen wurden angepasst
  * Readme: Neuer Contributor hinzugefügt und unbenutzten Code bereinigt

### 2.9.4 ###
* **English**
  * Enhancement: Add filter to allow ajax calls
  * Tweak: Better wording for BBCode feature in plugin description
  * Tweak: Better screenshots in the plugin directory
  * Maintenance: Tested up to WordPress 5.7

* **Deutsch**
  * Verbesserung: Filter hinzugefügt, um Ajax-Aufrufe zuzulassen
  * Tweak: Bessere Formulierung für BBCode-Funktion in Plugin-Beschreibung
  * Tweak: Bessere Screenshots im Plugin-Verzeichnis
  * Wartung: Getestet mit WordPress 5.7

### 2.9.3 ###
* **English**
  * Fixed: Compatibility with WordPress 5.5
  * Fixed: Undefined index on spam list page
  * Tweak: Better wording on settings page
  * Tweak: AMP compatibility
  * Tweak: Protect CSS from overwrite through bad themes

* **Deutsch**
  * Fix: Kompatibilität mit WordPress 5.5
  * Fix: Undefined index in Spamliste
  * Tweak: Inklusivere Sprache unter Einstellungen
  * Tweak: AMP-Kompatibilität
  * Tweak: Schütze CSS besser vor Überschreiben durch schlechte Themes

### 2.9.2 ###
* **English**
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


* **Deutsch**
  * Fix: Lösche Kommentarmeta beim Löschen von Spamkommentaren. Für das Aufräumen alter verwaister Kommentarmetas empfehlen wir die Verwendung von [WP Sweep](https://de.wordpress.org/plugins/wp-sweep/)
  * Fix: Dashboard Statistiken zeigten falschen Wert
  * Tweak: Änderung des autocomplete Attributs zu "new-password"
  * Tweak: Kompatibilität mit Autoptimize verbessert
  * Tweak: "Blacklist/Whitelist" umbenannt
  * Tweak: Neue Spamerkennungsmuster hinzugefügt
  * Tweak: UI und Textverbesserungen
  * Tweak: Erhöhte Kompatibilität mit einigen Serverkonfigurationen
  * Tweak: Kommentare nach Spamgrund sortier- und filterbar gemacht
  * Tweak: Neuer Spamgrund für manuell markierten Spam eingeführt
  * Maintenance: Ungenutzter Code wurde gelöscht
  * Maintenance: Der Fake IP check wurde entfernt. Dieser war unzuverlässig und produzierte falsche Ergebnisse
  * Maintenance: Einige Probleme mit unseren Coding standards wurden gefixt
  * Maintenance: Getestet bis WordPress 5.4
  * Maintenance: Getestet bis PHP 7.4

### 2.9.1 ###
* **English**
  * Improved backend accessibility
  * Prefilled comment textareas do now work with the honeypot
  * Compatible with the AMP plugin (https://wordpress.org/plugins/amp/)
  * Improved dashboard tooltips
  * Improvements for the language detection API
  * Scalable IP look up for local spam database


* **Deutsch**
  * Verbesserte Barrierefreiheit im Backend
  * Vorausgefüllte Kommentarfelder arbeiten jetzt mit dem Honeypot zusammen
  * Kompatibel mit dem AMP Plugin (https://wordpress.org/plugins/amp/)
  * Verbesserte Tooltips im Dashboard
  * Verbesserte Kommunikation mit der Spracherkennungs-API
  * Skalierbarer IP-Abgleich für den lokalen Datenbank-Check.

### 2.9.0 ###
* **English**
  * Introduction of coding standards.
  * Switch to franc language detection API for the language check.
  * Do not longer overwrite the IP address WordPress saves with the comment by using `pre_comment_user_ip`.
  * Do not show "Trust commenters with a Gravatar" if the "Show Gravatar" option is not set.
  * Skip the checks, when I ping myself.
  * Fixes some wrong usages of the translation functions.
  * Use the regular expressions check also for trackbacks.
  * Add option to delete Antispam Bee related data when plugin gets deleted via the admin interface.
  * Save a hashed + salted IP for every comment
  * New check for incoming Trackbacks.
  * Introduction of behat tests.
  * Updates the used JavaScript library for the statistics widget.
  * Bugfix in the "Comment form used outside of posts" option.

* **Deutsch**
  * Einführung von Coding Standards.
  * Wechsel auf die Franc Spracherkennungs API für den Sprach-Check.
  * Beendet das Überschreiben der IP Adresse via `pre_comment_user_ip`, welche WordPress mit dem Kommentar speichert.
  * Zeige die Option "Vertraue Kommentaren mit Gravatar" nur an wenn die Option "Zeige Gravatar" aktiviert ist.
  * Überspringe die Filter, wenn ich mich selbst anpinge.
  * Repariert einige falsche Verwendungsweisen der Übersetzungsfunktionalitäten.
  * Wende den reguläre Ausdrücke Check auch auf Trackbacks an.
  * Option hinzugefügt, dass Daten von Antispam Bee gelöscht werden, wenn das Plugin über das Admin Interface gelöscht wird.
  * Speichere für jeden Kommentar eine salted Hash der IP Adresse.
  * Ein neuer Check für eingehende Trackbacks.
  * Einführung von Behat tests.
  * Aktualisiert die genutzte JavaScript Bibliothek für das Statistik Widget.
  * Bugfix in der "Kommentarformular wird außerhalb von Beiträgen verwendet" Einstellung

### 2.8.1 ###

* **English**
  * PHP 5.3 compatibility
  * Bugfix where a spam trackback produced a fatal error
  * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/8?closed=1

* **Deutsch**
  * PHP 5.3 Kompatibilität wieder hergestellt
  * Bugfix: Ein Spam Trackback produzierte einen Fatal Error
  * Mehr Details: https://github.com/pluginkollektiv/antispam-bee/milestone/8?closed=1

### 2.8.0 ###

* **English**
  * Removed stopforumspam.com to avoid potential GDPR violation
  * Improves IP handling to comply with GDPR
  * Improves PHP7.2 compatibility
  * Fixes small bug on mobile views
  * Allow more than one language in language check
  * Minor interface improvements
  * Remove old russian and Dutch translation files
  * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/4?closed=1

* **Deutsch**
  - Entfernt stopforumspam.com zur Vorbeugung möglicher DSGVO-Verletzungen
  - Verändert den Umgang mit IP-Adressen um der DSGVO zu entsprechen
  - Verbessert PHP7.2-Kompatibilität
  - Behebt einen CSS-Bugfix der mobilen Darstellung
  - Erlaube mehr als eine Sprache im Sprachencheck
  - Verberesserungen an der Benutzeroberfläche
  - Entfernt alte russische und holländische Sprachversionen
  - Mehr Details: https://github.com/pluginkollektiv/antispam-bee/milestone/4?closed=1

### 2.7.1 ###

* **English**
  * Fixes an incompatibility with Chrome autofill
  * Fixes some incompatibilities with other plugins/themes where the comment field was left empty
  * Support for RTL
  * Solve some translation/language issues
  * A new filter to add languages to the language check
  * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/6?closed=1
* **Deutsch**
  - Behebt eine Inkompatibilität mit Chromes Autofill-Funktion
  - Behebt einige Inkompatibilitäten mit anderen Plugins/Themes, wo das Kommentarfeld leer bliebt
  - Unterstützt RTL-Sprachen
  - Behebt einige Probleme im Bereich Sprache/Übersetzung
  - Bietet einen neuen Filter zum HInzufügen von Sprachen zum Sprach-Check
  - Mehr Details: https://github.com/pluginkollektiv/antispam-bee/milestone/6?closed=1

### 2.7.0 ###
* **English**
   * Country check is back again (thanks to Sergej Müller for his amazing work and the service page)
   * Improved Honeypot
   * Language check through Google Translate API is back again (thanks to [Simon Kraft](https://simonkraft.de/) for offering to cover the costs)
   * More default Regexes
   * Unit Test Framework
   * Accessibility and GUI improvements
   * An [english documentation](https://github.com/pluginkollektiv/antispam-bee/wiki) is now available, too. Some corrections in the german documentation.
   * Some bugfixes - Among other things for WPML compatibility
   * For more details see https://github.com/pluginkollektiv/antispam-bee/milestone/3?closed=1

* **Deutsch**
   * Die Länderprüfung ist wieder zurück (dank an Sergej Müller für seine fantastische Arbeit und die Service-Seite)
   * Der Honeypot wurde verbessert
   * Die Sprachenprüfung über die Google Translate API ist wieder zurück (Dank an [Simon Kraft](https://simonkraft.de/), der sich angeboten hat, die Kosten zu übernehmen)
   * Mehr Standard-Regexe
   * Verbesserungen an Barrierefreiheit und Benutzer-Oberfläche
   * Eine [englische Dokumentation](https://github.com/pluginkollektiv/antispam-bee/wiki) ist jetzt verfügbar. Einige Korrekturen in der deutschen Dokumentation.
   * Einige Fehlerkorrekturen - Unter anderem für WPML-Kompatibilität
   * Mehr Details: https://github.com/pluginkollektiv/antispam-bee/milestone/3?closed=1

### 2.6.9 ###
* **English**
   * Updates donation links throughout the plugin
   * Fixes an error were JavaScript on the dashboard was erroneously being enqueued
   * Ensures compatibility with the latest WordPress version
* **Deutsch**
   * Aktualisierung der Spenden Links im gesamten Plugin
   * Behebt einen Fehler, durch den auf dem Dashboard fälschlicherweise JavaScript geladen wird
   * Gewährleistet die Kompatibilität mit der neuesten WordPress-Version

### 2.6.8 ###
* **English**
   * added a POT file
   * updated German translation, added formal version
   * updated plugin text domain to include a dash instead of an underscore
   * updated, translated + formatted README.md
   * updated expired link URLs in plugin and languages files
   * updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)
* **Deutsch**
   * eine POT-Datei hinzugefügt
   * deutsche Übersetzung aktualisiert, formale Version hinzugefügt
   * Die Text Domain des Plugins in der ReadMe aktualisiert. Statt einem Unterstrich enthält der Name nun ein Bindestrich.
   * README.md aktualisiert, übersetzt und formatiert
   * verwaiste Link-Adressen in dem Plugin und den Sprachdateien aktualisiert
   * [Plugin Autor](https://gist.github.com/glueckpress/f058c0ab973d45a72720) aktualisiert

### 2.6.7 ###
* **English**
   * Removal of functions *Block comments from specific countries* and *Allow comments only in certain language* for financial reasons
* **Deutsch**
   * Entfernung der Funktionen *Kommentare nur in einer Sprache zulassen* und *Bestimmte Länder blockieren bzw. erlauben* aus finanziellen Gründen - [Hintergrund-Informationen](https://antispambee.pluginkollektiv.org/news/2015/removal-of-allow-comments-only-in-certain-language/)

### 2.6.6 ###
* **English**
    * Switch to the official Google Translation API
    * *Release time investment (Development & QA): 2.5 h*
* **Deutsch**
    * (Testweise) Umstellung auf die offizielle Google Translation API
    * *Release-Zeitaufwand (Development & QA): 2,5 Stunden*

### 2.6.5 ###
* **English**
   * Fix: Return parameters on `dashboard_glance_items` callback / thx [@toscho](https://twitter.com/toscho)
   * New function: [Trust commenters with a Gravatar](https://antispambee.pluginkollektiv.org/documentation#gravatar) / thx [@glueckpress](https://twitter.com/glueckpress)
   * Additional plausibility checks and filters
   * *Release time investment (Development & QA): 12 h*
* **Deutsch**
   * Fix: Parameter-Rückgabe bei `dashboard_glance_items` / thx [@toscho](https://twitter.com/toscho)
   * Neue Funktion: [Kommentatoren mit Gravatar vertrauen](https://antispambee.pluginkollektiv.org/de/dokumentation#gravatar) / thx [@glueckpress](https://twitter.com/glueckpress)
   * Zusätzliche Plausibilitätsprüfungen und Filter
   * *Release-Zeitaufwand (Development & QA): 12 Stunden*

### 2.6.4 ###
* **English**
   * Consideration of the comment time (Spam if a comment was written in less than 5 seconds)
   * *Release time investment (Development & QA): 6.25 h*
* **Deutsch**
   * Berücksichtigung der Kommentarzeit (Spam, wenn ein Kommentar in unter 5 Sekunden verfasst) - [Hintergrund-Informationen](https://antispambee.pluginkollektiv.org/news/2014/antispam-bee-2-6-4/)
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

### 2.5.7 ###
* **English**
   * Optional logfile with spam entries e.g. for [Fail2Ban](https://help.ubuntu.com/community/Fail2ban)
   * Filter `antispam_bee_notification_subject` for a custom subject in notifications
* **Deutsch**
   * Optionale Spam-Logdatei z.B. für [Fail2Ban](https://wiki.ubuntuusers.de/fail2ban/)
   * Filter `antispam_bee_notification_subject` für eigenen Betreff in Benachrichtigungen

### 2.5.6 ###
* **English**
   * [Added new detection/patterns for spam comments](https://antispambee.pluginkollektiv.org/news/2013/new-patterns-in-antispam-bee-2-5-6/)
* **Deutsch**
   * [Neue Erkennungsmuster für Spam hinzugefügt](https://antispambee.pluginkollektiv.org/de/news/2013/neue-erkennungsmuster-in-antispam-bee-2-5-6/)

### 2.5.5 ###
* **English**
   * Detection and filtering of spam comments that try to exploit the latest [W3 Total Cache and WP Super Cache Vulnerability](http://blog.sucuri.net/2013/05/w3-total-cache-and-wp-super-cache-vulnerability-being-targeted-in-the-wild.html).
* **Deutsch**
   * Erkennung und Ausfilterung von Spam-Kommentaren, die versuchen, [Sicherheitslücken von W3 Total Cache und WP Super Cache](http://blog.sucuri.net/2013/05/w3-total-cache-and-wp-super-cache-vulnerability-being-targeted-in-the-wild.html) auszunutzen. [Ausführliche Informationen](https://antispambee.pluginkollektiv.org/de/news/2013/antispam-bee-nun-auch-als-antimalware-plugin/).

### 2.5.4 ###
* **English**
   * Jubilee edition
   * New mascot for Antispam Bee
   * Advanced Scanning on IP, URL and e-mail address of incoming comments in local blog spam database
* **Deutsch**
   * Jubiläumsausgabe: [Details zum Update](https://plus.googlehttps://antispambee.pluginkollektiv.org/de/news/2013/jubilaeumsausgabe-antispam-bee-2-5-4/)
   * Neues Maskottchen für Antispam Bee
   * Erweiterte Prüfung eingehender Kommentare in lokaler Blog-Spamdatenbank auf IP, URL und E-Mail-Adresse

### 2.5.3 ###
* **English**
   * Optimization of regular expression
* **Deutsch**
   * Optimierung des Regulären Ausdrucks

### 2.5.2 ###
* **English**
   * New: Use of regular expressions with predefined and own identification patterns
   * Change the filter order
   * Improvements to the language file
* **Deutsch**
   * Neu: [Reguläre Ausdrücke anwenden](hhttps://antispambee.pluginkollektiv.org/de/dokumentation#regex) mit vordefinierten und eigenen Erkennungsmustern
   * Änderung der Filter-Reihenfolge
   * Verbesserungen an der Sprachdatei

### 2.5.1 ###
* **English**
   * Treat BBCode as spam
   * IP anonymization in the country evaluation
   * More transparency by added Privacy Policy
   * PHP 5.2.4 as a requirement (is also the prerequisite for WP 3.4)
* **Deutsch**
   * [BBCode im Kommentar als Spamgrund](hhttps://antispambee.pluginkollektiv.org/de/dokumentation#bbcode)
   * IP-Anonymisierung bei der Länderprüfung
   * [Mehr Transparenz](https://antispambee.pluginkollektiv.org/de/news/2012/datenschutz-update/) durch hinzugefügte Datenschutzhinweise
   * PHP 5.2.4 als Voraussetzung (ist zugleich die Voraussetzung für WP 3.4)

### 2.5.0 ###
* **English**
   * [Edition 2012](https://antispambee.pluginkollektiv.org/news/2012/edition-2012/)
* **Deutsch**
   * [Edition 2012](https://antispambee.pluginkollektiv.org/de/news/2012/edition-2012/)

### 2.4.6 ###
* **English**
   * Russian translation
   * Change the secret string
* **Deutsch**
   * Russische Übersetzung
   * Veränderung der Secret-Zeichenfolge

### 2.4.5 ###
* **English**
   * Revised layout settings
   * Deletion of Project Honey Pot
   * TornevallNET as new DNSBL service
   * WordPress 3.4 as a minimum requirement
   * WordPress 3.5 support
   * Recast of the online manual
* **Deutsch**
   * Überarbeitetes Layout der Einstellungen
   * Streichung von Project Honey Pot
   * TornevallNET als neuer DNSBL-Dienst
   * WordPress 3.4 als Mindestvoraussetzung
   * WordPress 3.5 Unterstützung
   * Neufassung des Online-Handbuchs

### 2.4.4 ###
* **English**
   * Technical and visual support for WordPress 3.5
   * Modification of the file structure: from `xyz.dev.css` to `xyz.min.css`
   * Retina screenshot
* **Deutsch**
   * Technische und optische Unterstützung für WordPress 3.5
   * Änderung der Dateistruktur: von `xyz.dev.css` zu `xyz.min.css`
   * Retina Bildschirmfoto

### 2.4.3 ###
* **English**
   * Check for basic requirements
   * Remove the sidebar plugin icon
   * Set the Google API calls to SSL
   * Compatibility with WordPress 3.4
   * Add retina plugin icon on options
   * Depending on WordPress settings: anonymous comments allowed
* **Deutsch**
   * Mindestvoraussetzungen werden nun überprüft
   * Entfernung des Plugin Icons in der Sidebar
   * Google API Aufrufe auf SSL umgestellt
   * Kompatibilität mit WordPress 3.4
   * Retina Plugin Icon in den Einstellungen hinzugefügt
   * In Abhängigkeit zu den Wordpress-Einstellungen: anonyme Kommentare erlauben

### 2.4.2 ###
* **English**
   * New geo ip location service (without the api key)
   * Code cleanup: Replacement of `@` characters by a function
   * JS-Fallback for missing jQuery UI
* **Deutsch**
   * Neuer IP-Geolocation-Dienst (ohne api key)
   * Quelltext aufgeräumt: Austausch von `@` Zeichen durch eine Funktion
   * S-Fallback für fehlende jQuery UI

### 2.4.1 ###
* **English**
   * Add russian translation
   * Fix for the textarea replace
   * Detect and hide admin notices
* **Deutsch**
   * Russian Übersetzung hinzugefügt
   * Fehlerbehebung bei dem ersetzten Textfeld
   * Erkennen und verstecken von Admin-Mitteilungen

### 2.4 ###
* **English**
   * Support for IPv6
   * Source code revision
   * Delete spam by reason
   * Changing the user interface
   * Requirements: PHP 5.1.2 and WordPress 3.3
* **Deutsch**
   * Unterstützung für IPv6
   * Quellcode Überarbeitung
   * Spam mit Begründung löschen
   * Änderung der Benutzeroberfläche
   * Voraussetzungen: PHP 5.1.2 und WordPress 3.3

### 2.3 ###
* **English**
   * Xmas Edition
* **Deutsch**
   * Weihnachtsausgabe

### 2.2 ###
* **English**
   * Interactive Dashboard Stats
* **Deutsch**
   * Interaktive Dashboard Statistik

### 2.1 ###
* **English**
   * Remove Google Translate API support
* **Deutsch**
   * Google Translate API Unterstützung entfernt

### 2.0 ###
* **English**
   * Allow comments only in certain language (English/German)
   * Consider comments which are already marked as spam
   * Dashboard Stats: Change from canvas to image format
   * System requirements: WordPress 2.8
   * Removal of the migration script
   * Increase plugin security
* **Deutsch**
   * Kommentare nur in bestimmten Sprachen erlauben (Englisch/Deutsch)
   * Das Plugin kann nun Kommentare berücksichtigen, die bereits als Spam markiert wurden
   * Dashboard-Statistik: Wechsel von canvas zu einem Bildformat
   * Systemvoraussetzungen: WordPress 2.8
   * Entfernung des Migrationsscriptes
   * Plugin Sicherheit verbessert

### 1.9 ###
* **English**
   * Dashboard History Stats (HTML5 Canvas)
* **Deutsch**
   * Dashboard Statistiken (HTML5 Canvas)

### 1.8 ###
* **English**
   * Support for the new IPInfoDB API (including API Key)
* **Deutsch**
   * Unterstützung der neuen IPInfoDB API (einschließlich API-Key)

### 1.7 ###
* **English**
   * Black and whitelisting for specific countries
   * "Project Honey Pot" as a optional spammer source
   * Spam reason in the notification email
   * Visual refresh of the notification email
   * Advanced GUI changes + Fold-out options
* **Deutsch**
   * Schwarze und weiße Liste für bestimmte Länder
   * "Project Honey Pot" als optionale Spammer-Quelle
   * Spam-Begründung in der E-Mail-Benachrichtigung
   * Visuelle Überarbeitung der E-Mail-Benachrichtigung
   * Erweiterte Benutzeroberflächenanpassungen + ausklappbare Einstellungen

### 1.6 ###
* **English**
   * Support for WordPress 3.0
   * System requirements: WordPress 2.7
   * Code optimization
* **Deutsch**
   * Unterstützung für WordPress 3.0
   * Systemvoraussetzungen: WordPress 2.7
   * Quelltext optimiert

### 1.5 ###
* **English**
   * Compatibility with WPtouch
   * Add support for do_action
   * Translation to Portuguese of Brazil
* **Deutsch**
   * Kompatibilität mit WPtouch
   * Unterstützung für do_action hinzugefügt
   * Übersetzung auf brasilianisches Portugiesisch

### 1.4 ###
* **English**
   * Enable stricter inspection for incomming comments
   * Do not check if the author has already commented and approved
* **Deutsch**
   * strengere Kontrolle für eingehende Kommentare aktiviert
   * Nicht auf Spam überprüfen, wenn der Autor bereits kommentiert hat und freigegeben wurde

### 1.3 ###
* **English**
   * New code structure
   * Email notifications about new spam comments
   * Novel Algorithm: Advanced spam checking
* **Deutsch**
   * Neue Quelltextstruktur
   * E-Mail-Benachrichtigungen über neue Spam-Kommentare
   * Neuartiger Algorithmus: Erweiterte Spamprüfung

### 1.2 ###
* **English**
   * Antispam Bee spam counter on dashboard
* **Deutsch**
   * Antispam Bee Spam-Zähler auf dem Dashboard

### 1.1 ###
* **English**
   * Adds support for WordPress new changelog readme.txt standard
   * Various changes for more speed, usability and security
* **Deutsch**
   * Unterstützung des neuen readme.txt Standards für das Änderungsprotokoll hinzugefügt
   * Verschiedene Änderungen für mehr Geschwindigkeit, Benutzerfreundlichkeit und Sicherheit

### 1.0 ###
* **English**
   * Adds WordPress 2.8 support
* **Deutsch**
   * WordPress 2.8 Unterstützung hinzugefügt

### 0.9 ###
* **English**
   * Mark as spam only comments or only pings
* **Deutsch**
   * nur Kommentare oder nur Pings als Spam markieren

### 0.8 ###
* **English**
   * Optical adjustments of the settings page
   * Translation for Simplified Chinese, Spanish and Catalan
* **Deutsch**
   * Optische Anpassungen der Einstellungsseite
   * Übersetzung für vereinfachtes Chinesisch, Spanisch und Katalanisch

### 0.7 ###
* **English**
   * Spam folder cleanup after X days
   * Optional hide the &quot;MARKED AS SPAM&quot; note
   * Language support for Italian and Turkish
* **Deutsch**
   * Spam-Ordner Bereinigung nach n Tagen
   * Optionales verstecken des &quot;als Spam markiert&quot; Hinweises
   * Übersetzungen für Italienisch und Türkisch

### 0.6 ###
* **English**
   * Language support for English, German, Russian
* **Deutsch**
   * Übersetzungen für Englisch, Deutsch und Russisch

### 0.5 ###
* **English**
   * Workaround for empty comments
* **Deutsch**
   * Problembehebung für leere Kommentare

### 0.4 ###
* **English**
   * Option for trackback and pingback protection
* **Deutsch**
   * Einstellung für den Trackback- und Pingback-Schutz

### 0.3 ###
* **English**
   * Trackback and Pingback spam protection
* **Deutsch**
   * Trackback und Pingback Spam-Schutz
