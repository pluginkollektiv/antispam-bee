Feature: Spam Comments

  @test
  Scenario: Normal spam test
    Given I am on "/?p=1"
    Then I fill in "comment" with "Release the hounds!"
    Then I fill in "23b968f9bc" with "Release the hounds!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns"
    Then I should see "Honeypot"

