Feature: Trackbacks

  @db
  Scenario: BBCode
	Given the option "bbcode_check,flag_spam" is set
	Given I send a trackback with the title "Nuclear Power Plants" and the excerpt "use [url='http://example.com']bbCode[/url]" and the url "http://nuclear-power.rocks" and the blog_name "Mr. Burns Spam Corp." to the post 1

	Given I am logged in as admin
	Given I am on "/wp-admin/edit-comments.php?comment_status=spam"
	Then I should see "Nuclear Power Plants"
	Then I should see "BBCode"