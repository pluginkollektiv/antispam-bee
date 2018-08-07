Feature: Basics

  Background:
    Given I am logged in with the name admin and the password abc

  Scenario: I can see the plugin
    And I am on the plugins-page
    Then I should see "Antispam Bee"

  @db
  Scenario: I can activate the plugin
    Given The plugin "antispam-bee" is deactivated
    And I am on the plugins-page
    And I activate the plugin "antispam-bee"
    Then I should see an status message that says "Plugin activated."

  @db
  Scenario: I can deactivate the plugin
    Given The plugin "antispam-bee" is activated
    And I am on the plugins-page
    And I deactivate the plugin "antispam-bee"
    Then I should see an status message that says "Plugin deactivated."