Feature: More settings

  @javascript @db
  Scenario: Spam counter enabled
    Given the option "dashboard_count,dashboard_chart,regexp_check,spam_count" is set

    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then I should see "No data available."
    Then I should not see an "#ab_chart" element

  @javascript @db
  Scenario: Spam counter works
    Given I am on "/?p=1"
    Given the option "dashboard_count,dashboard_chart,regexp_check" is set
    Then I fill in "comment" with "Release the viagra!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"

    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then I should see an "#ab_chart" element