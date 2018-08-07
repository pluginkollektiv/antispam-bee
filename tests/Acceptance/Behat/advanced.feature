Feature: Advanced settings

  @db
  Scenario: Spam is saved in database
    Given I am on "/?p=1"
    Given the option "flag_spam" is set
    Then I fill in "comment" with "Release the hounds!"
    Then I fill in "secret" with "Release the hounds!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns"
    Then I should see "Honeypot"

  @db
  Scenario: Spam is not saved in database
    Given I am on "/?p=1"
    Given the option "flag_spam" is not set
    Then I fill in "comment" with "Release the hounds!"
    Then I fill in "secret" with "Release the hounds!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should not see "Hello world"
    Then I should not see "Notice"
    Then I should see "Spam deleted."

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Mr. Burns"
    Then I should not see "Honeypot"

  @db
  Scenario: Save spam reason
    Given I am on "/?p=1"
    Given the option "flag_spam" is set
    Given the option "no_notice" is not set
    Then I fill in "comment" with "Release the hounds!"
    Then I fill in "secret" with "Release the hounds!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns"
    Then I should see "Honeypot"

  @db
  Scenario: Do not save spam reason
    Given I am on "/?p=1"
    Given the option "flag_spam,no_notice" is set
    Then I fill in "comment" with "Release the hounds!"
    Then I fill in "secret" with "Release the hounds!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns"
    Then I should not see "Honeypot"