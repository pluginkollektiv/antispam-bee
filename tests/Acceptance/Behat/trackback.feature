Feature: Trackbacks

  @db
  Scenario: BBCode
    Given the option "bbcode_check,flag_spam" is set
    Given I send a trackback with the title "Nuclear Power Plants" and the excerpt "use [url='http://example.com']bbCode[/url]" and the url "http://nuclear-power.rocks" and the blog_name "Mr. Burns Spam Corp." to the post 1

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Nuclear Power Plants"
    Then I should see "BBCode"

  @db
  Scenario: Local Spam DB URL
    Given the option "spam_ip,flag_spam" is set
    Given a comment exists with "Where is this enter button?" by "Mr. Burns" with email "montgomery.c.burns.1866@nuclear-secrets.com", URL "http://nuclear-power.rocks", IP "127.0.0.2", date "2010-12-12 12:00:00" and status "spam"
    Given I send a trackback with the title "Nuclear Power Plants" and the excerpt "use electrons for quicker results!" and the url "http://nuclear-power.rocks" and the blog_name "Mr. Burns Spam Corp." to the post 1

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Nuclear Power Plants"
    Then I should see "Local DB Spam"

  @javascript @db
  Scenario: Local Spam DB IP
    Given the option "regexp_check,spam_ip,flag_spam" is set
    Given I am on "/?p=1"
    Then I fill in "comment" with "Viagra is the way to go!"
    Then I fill in "author" with "Montgomery"
    Then I fill in "email" with "montgomery.c.burns.1866@aol.com"
    Then I fill in "url" with "http://nuclear-secrets.com"
    Then I press "submit"
    Then I wait 15 seconds
    Given I send a trackback with the title "Nuclear Power Plants" and the excerpt "use electrons for quicker results!" and the url "http://nuclear-power.rocks" and the blog_name "Mr. Burns Spam Corp." to the post 1

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Nuclear Power Plants"
    Then I should see "Local DB Spam"

  @db
  Scenario: Regex
    Given the option "regexp_check,flag_spam" is set
    Given I send a trackback with the title "Viagra" and the excerpt "has more use cases than you think" and the url "http://explore.viagra" and the blog_name "Mr. Burns Spam Corp." to the post 1

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns Spam Corp."
    Then I should see "Regular Expression"

  @db
  Scenario: Title is name
    Given the option "flag_spam" is set
    Given I send a trackback with the title "Mr. Burns Spam Corp." and the excerpt "has more use cases than you think" and the url "http://explore.viagra" and the blog_name "Mr. Burns Spam Corp." to the post 1

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns Spam Corp."
    Then I should see "Identical Post title and blog title"

  @db
  Scenario: Comment Language
    Given the option "translate_api,flag_spam" is set
    Given the option "translate_lang" has the array value "de"
    Given I send a trackback with the title "The English language" and the excerpt "has more use cases than you think" and the url "http://explore.co.uk" and the blog_name "Mr. Burns Spam Corp." to the post 1

    Given I am logged in as admin
    Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
    Then I should see "Mr. Burns Spam Corp."
    Then I should see "Comment Language"