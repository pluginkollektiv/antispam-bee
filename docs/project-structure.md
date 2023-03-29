# Project Structure

Compared to version 2 of Antispam Bee, the project structure has changed a lot. We now do not have one large file that contains all spam detection, but various classes doing that.

The most important part of ASB 3 are rules and post processors. Both, rules and post processors, can be controllable via the options page or work invisible in the background without registering a control.

- `src` – this directory contains all of our PHP files except the main plugin file.
- `src/load.php` – registers hooks for activation/deactivation/uninstall and, more important, loads the ASB modules.
- `src/Admin` – contains the files that are responsible for displaying things in the backend, like the spam counter in the dashboard widget or the settings page.
- `src/Crons` – cron-related files.
- `src/GeneralOptions` – contains the classes for the general options, like showing statistics in the Dashboard.
- `src/Handlers` – classes handling things, like incoming comments or linkbacks, and applying rules and post processors to a reaction.
- `src/Helpers` – helpers for various tasks.
- `src/Interfaces` – a few interfaces.
- `src/PostProcessors` – the ASB core post processors.
- `src/Rules` – the ASB core anti-spam rules.
