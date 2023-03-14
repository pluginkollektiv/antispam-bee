# Adding rules

ASB 3 makes it possible for third-parties to add custom rules.

Each Rule is a class that extends the `ControllableBase` (if your rule has options, like for disabling/enabling) or `Base` (if your rule works invisible in the background) class and implements the `SpamReason` interface.
