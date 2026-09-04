@block @block_catquiz_feedbackwizard
Feature: The CATQuiz Settings Wizard block is gated by capability
  In order to configure CAT tests without touching the technical admin pages
  As a teacher
  I need the Settings Wizard block to offer an entry point, and to stay silent
  for users who may not use it

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | teacher1 | Teacher   | One      | teacher1@test.com |
      | student1 | Student   | One      | student1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: A teacher can add the block and sees the wizard entry point
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "CATQuiz Wizard" block
    Then I should see "Start the CATQuiz wizard"

  Scenario: A student does not see the wizard entry point
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "CATQuiz Wizard" block
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Start the CATQuiz wizard"
