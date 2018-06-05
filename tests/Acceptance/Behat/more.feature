Feature: More settings

  @javascript @db
  Scenario: Spam counter disabled
    Given the option "dashboard_count" is not set

    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then I should not see "0 Blocked"
	Then I should not see "Fatal"
	Then I should not see "Notice"

  @javascript @db
  Scenario: Spam counter enabled
    Given the option "dashboard_count" is set

    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then I should see "0 Blocked"
	Then I should not see "Fatal"
	Then I should not see "Notice"

  @javascript @db
  Scenario: Spam counter works
    Given I am on "/?p=1"
    Given the option "dashboard_count,regexp_check" is set
    Then I fill in "comment" with "Release the viagra!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then the value of the option "spam_count" is "1"
    Given I am on "/?p=1"
    Then I fill in "comment" with "Release the viagra, again!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
	Then I should not see "Fatal"
	Then I should not see "Notice"

    Then the value of the option "spam_count" is "2"

    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then I should see "2 Blocked"
	Then I should not see "Fatal"
	Then I should not see "Notice"

  @javascript @db
  Scenario: Dashboard chart enabled
    Given the option "dashboard_chart" is set
    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then the "#ab_chart" element should contain "No data available."
	Then I should not see "Fatal"
	Then I should not see "Notice"

  @javascript @db
  Scenario: Dashboard chart disabled
    Given the option "dashboard_chart" is not set
    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then I should not see "No data available."
	Then I should not see "Fatal"
	Then I should not see "Notice"

  @javascript @db
  Scenario: Spam counter works
    Given I am on "/?p=1"
    Given the option "dashboard_chart,regexp_check" is set
    Then I fill in "comment" with "Release the viagra!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Given I am on "/?p=1"
    Then I fill in "comment" with "Release the viagra, again!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"

    Given I am logged in as admin
    Given I am on "/wp-admin/"
    Then the "#ab_chart_data td" element should contain "2"
	Then I should not see "Fatal"
	Then I should not see "Notice"