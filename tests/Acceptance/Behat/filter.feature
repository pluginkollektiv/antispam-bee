Feature: Filter settings

  @db
  Scenario: Honeypot
    Given the secret is fixed
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
    Given the secret is fixed
    Given I am on "/?p=1"
    Given the option "flag_spam" is not set
    Then I fill in "comment" with "Release the hounds!"
    Then I fill in "secret" with "Release the hounds!"
    Then I fill in "author" with "Mr. Burns"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Spam deleted"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Mr. Burns"
    Then I should not see "Honeypot"
    Given I am on "/wp-admin/edit-comments.php"
    Then I should not see "Mr. Burns"
    Then I should not see "Honeypot"

  @javascript @db
  Scenario: Local Spam DB IP
    Given the option "regexp_check,spam_ip,flag_spam" is set
	Given I am on "/?p=1"
	Then I fill in "comment" with "you can Buy amazing Neutrons here!"
	Then I fill in "author" with "Montgomery"
	Then I fill in "email" with "montgomery.c.burns.1866@aol.com"
	Then I fill in "url" with "http://nuclear-secrets.com"
	Then I press "submit"
	Then I should not see "Fatal"
	Then I should see "Hello world"
	Then I should not see "Notice"
	Then I wait 15 seconds
	Given I am on "/?p=1"
    Then I fill in "comment" with "Excellent indeed!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.info"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Monty"
    Then I should see "Local DB Spam"

  @javascript @db
  Scenario: Local Spam DB Deactivated
    Given the option "flag_spam" is set
    Given a comment exists with "Where is this enter button?" by "Mr. Burns" with email "montgomery.c.burns.1866@nuclear-secrets.com", URL "http://nuclear-secrets.com", IP "127.0.0.1", date "2010-12-12 12:00:00" and status "spam"
    Given I am on "/?p=1"
    Then I fill in "comment" with "Excellent!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.info"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "Local DB Spam"
    Given I am on "/wp-admin/edit-comments.php"
    Then I should see "Monty"

  @javascript @db
  Scenario: Local Spam DB Email
    Given the option "spam_ip,flag_spam" is set
    Given a comment exists with "Where is this enter button?" by "Mr. Burns" with email "montgomery.c.burns.1866@nuclear-secrets.com", URL "http://nuclear-secrets.com", IP "127.0.0.2", date "2010-12-12 12:00:00" and status "spam"
    Given I am on "/?p=1"
    Then I fill in "comment" with "Excellent!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "montgomery.c.burns.1866@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.info"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Monty"
    Then I should see "Local DB Spam"

  @javascript @db
  Scenario: Local Spam DB URL
    Given the option "spam_ip,flag_spam" is set
    Given a comment exists with "Where is this enter button?" by "Mr. Burns" with email "montgomery.c.burns.1866@nuclear-secrets.com", URL "http://nuclear-secrets.com", IP "127.0.0.2", date "2010-12-12 12:00:00" and status "spam"
    Given I am on "/?p=1"
    Then I fill in "comment" with "Excellent!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Monty"
    Then I should see "Local DB Spam"

  @javascript @db
  Scenario: RegEx
	Given the option "regexp_check,flag_spam" is set
	Given I am on "/?p=1"
	Then I fill in "comment" with "Viagra helped me in those days."
	Then I fill in "author" with "Monty"
	Then I fill in "email" with "monty.1983@nuclear-secrets.com"
	Then I fill in "url" with "http://nuclear-secrets.com"
	Then I press "submit"
	Then I should not see "Fatal"
	Then I should see "Hello world"
	Then I should not see "Notice"

	Given I am logged in as admin
	Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
	Then I should see "Monty"
	Then I should see "Regular Expression"

  @javascript @db
  Scenario: RegEx luxurybrandsale
	Given the option "regexp_check,flag_spam" is set
	Given I am on "/?p=1"
	Then I fill in "comment" with "Come to our luxurybrandsale"
	Then I fill in "author" with "Monty"
	Then I fill in "email" with "monty.1983@nuclear-secrets.com"
	Then I fill in "url" with "http://nuclear-secrets.com"
	Then I press "submit"
	Then I should not see "Fatal"
	Then I should see "Hello world"
	Then I should not see "Notice"

	Given I am logged in as admin
	Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
	Then I should see "Monty"
	Then I should see "Regular Expression"

  @javascript @db
  Scenario: RegEx Disabled
    Given the option "regexp_check" is not set
    Given I am on "/?p=1"
    Then I fill in "comment" with "Viagra helped me in those days."
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "Regular Expression"
    Given I am on "/wp-admin/edit-comments.php"
    Then I should see "Monty"

  @javascript @db
  Scenario: BBCode
    Given the option "bbcode_check,flag_spam" is set
    Given I am on "/?p=1"
    Then I fill in "comment" with "This is also a [url=http://nuclear-secrets.com]nuclear[/url] page!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Monty"
    Then I should see "BBCode"

  @javascript @db
  Scenario: BBCode not activated
    Given the option "bbcode_check" is not set
    Given I am on "/?p=1"
    Then I fill in "comment" with "This is also a [url=http://nuclear-secrets.com]nuclear[/url] page!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "BBCode"

  @javascript @db
  Scenario: Comment Language
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "de"
    Given I am on "/?p=1"
    Then I fill in "comment" with "But English is my mothers tongue! This is outrageous! I send a comment anyway."
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Monty"
    Then I should see "Comment Language"

  @javascript @db
  Scenario: Comment Language
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "de"
    Given I am on "/?p=1"
    Then I fill in "comment" with "Well over d' Hund kummt, de kummt ok over d' Steert"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "Comment Language"
    Given I am on "/wp-admin/edit-comments.php"
    Then I should see "Monty"

  @javascript @db
  Scenario: Comment Language Too Short
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "de"
    Given I am on "/?p=1"
    Then I fill in "comment" with "A small text passes the test. Lets check this."
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "Comment Language"
    Given I am on "/wp-admin/edit-comments.php"
    Then I should see "Monty"

  @javascript @db
  Scenario: Comment Language Array
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "de,it"
    Given I am on "/?p=1"
    Then I fill in "comment" with "But English is my mothers tongue! This is outrageous! I send a comment anyway."
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Monty"
    Then I should see "Comment Language"

  @javascript @db
  Scenario: Comment Language OK
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "en"
    Given I am on "/?p=1"
    Then I fill in "comment" with "English is allowed!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "Comment Language"

  @javascript @db
  Scenario: Comment Language Array OK
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "it,en"
    Given I am on "/?p=1"
    Then I fill in "comment" with "English is allowed!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should not see "Monty"
    Then I should not see "Comment Language"

  @db @javascript
  Scenario: Later marked spam still works for spam db
    Given the option "flag_spam,spam_ip" is set
    Given I am on "/?p=1"
    Then I fill in "comment" with "Just a normal comment!"
    Then I fill in "author" with "Monty"
    Then I fill in "email" with "monty.1983@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php"
    Then I should see "Monty"
    Then I check "cb-select-all-1"
    Then I select "spam" from "bulk-action-selector-top"
    Then I press "doaction"

    Given I am an anonymous user
    Then I wait 15 seconds
    Given I am on "/?p=1"
    Then I fill in "comment" with "This is another comment from me, you will never guess!"
    Then I fill in "author" with "Montgomery"
    Then I fill in "email" with "monty.1982@nuclear-secrets.com"
    Then I fill in "url" with "http://nuclear-secrets-2000.com"
    Then I press "submit"
    Then I should not see "Fatal"
    Then I should see "Hello world"
    Then I should not see "Notice"

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Montgomery"
    Then I should see "Local DB Spam"