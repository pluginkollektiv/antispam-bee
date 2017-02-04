## Changelog ##

### Unreleased ###
* **English**
   * Country check is back again (thanks to Sergej Müller for his amazing work and the service page)
   * Language check through Google Translate API is back again (thanks to Simon Kraft of https://krafit.de/ for offering to cover the costs)
   * More default Regexes
   * Accessibility and Gui improvements
   * An [english documentation](https://github.com/pluginkollektiv/antispam-bee/wiki) is now available, too. Some corrections in the german documentation.
   * Some bugfixes - Among other things for WPML compatibility

* **Deutsch**
   * Die Länderprüfung ist wieder zurück (dank an Sergej Müller für seine fantastische Arbeit und die Service-Seite)
   * Die Sprachenprüfung über die Google Translate API ist wieder zurück (Dank an Simon Kraft von https://krafit.de/ weil er sich angeboten hat die Kosten zu übernehmen)
   * Mehr Standard-Regexe
   * Verbesserungen an der Zugänglichkeit und der Oberfläche
   * Eine [englische Dokumentation](https://github.com/pluginkollektiv/antispam-bee/wiki) ist jetzt verfügbar. Einige Korrekturen in der deutschen Dokumentation.
   * Einige Fehlerkorrekturen - Unter anderem für WPML-Kompatibilität

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
   * New function: [Trust commenters with a Gravatar](https://github.com/pluginkollektiv/antispam-bee/wiki/en-Documentation) / thx [@glueckpress](https://twitter.com/glueckpress)
   * Additional plausibility checks and filters
   * *Release time investment (Development & QA): 12 h*
* **Deutsch**
   * Fix: Parameter-Rückgabe bei `dashboard_glance_items` / thx [@toscho](https://twitter.com/toscho)
   * Neue Funktion: [Kommentatoren mit Gravatar vertrauen](https://github.com/pluginkollektiv/antispam-bee/wiki/de-Dokumentation) / thx [@glueckpress](https://twitter.com/glueckpress)
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
   * Optional logfile with spam entries e.g. for [Fail2Ban](https://help.ubuntu.com/community/Fail2ban)
   * Filter `antispam_bee_notification_subject` for a custom subject in notifications
* **Deutsch**
   * Optionale Spam-Logdatei z.B. für [Fail2Ban](https://wiki.ubuntuusers.de/fail2ban/)
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
   * Jubilee edition
   * New mascot for Antispam Bee
   * Advanced Scanning on IP, URL and e-mail address of incoming comments in local blog spam database
* **Deutsch**
   * Jubiläumsausgabe: [Details zum Update](https://plus.google.com/110569673423509816572/posts/3dq9Re5vTY5)
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
   * Neu: [Reguläre Ausdrücke anwenden](https://github.com/pluginkollektiv/antispam-bee/wiki/Dokumentation) mit vordefinierten und eigenen Erkennungsmustern
   * Änderung der Filter-Reihenfolge
   * Verbesserungen an der Sprachdatei
   * [Hintergrundinformationen zum Update](https://plus.google.com/110569673423509816572/posts/CwtbSoMkGrT)

### 2.5.1 ###
* **English**
   * Treat BBCode as spam
   * IP anonymization in the country evaluation
   * More transparency by added Privacy Policy
   * PHP 5.2.4 as a requirement (is also the prerequisite for WP 3.4)
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
