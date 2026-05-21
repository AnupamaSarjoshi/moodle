@local @local_learningoutcomes
Feature: Teacher tags course activities with learning outcomes
  In order to show students which activities support which outcomes
  As an editing teacher
  I need to tag activities with one or more learning outcomes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | name             |
      | page     | C1     | Test page        |
    And the following "local_learningoutcomes > learning outcomes" exist:
      | fullname               | shortname | course |
      | Apply bloom taxonomy   | LO1       | C1     |
      | Analyse case studies   | LO2       | C1     |
    And the following config values are set as admin:
      | enabled        | 1 | local_learningoutcomes |
      | coursesdefault | 1 | local_learningoutcomes |

  @javascript
  Scenario: Teacher tags an activity with learning outcomes
    Given I am on the "Test page" "page activity" page logged in as teacher1
    When I follow "Link to learning outcomes"
    And I check "LO1"
    And I press "Save"
    Then I should see "Activity outcome tags saved"

  @javascript
  Scenario: Teacher clears all outcome tags from an activity
    Given the "Test page" activity is tagged with outcome "LO1" in course "C1"
    And I am on the "Test page" "page activity" page logged in as teacher1
    When I follow "Link to learning outcomes"
    And I uncheck "LO1"
    And I press "Save"
    Then I should see "Activity outcome tags saved"

  @javascript
  Scenario: Teacher marks activity as decorative
    Given I am on the "Test page" "page activity" page logged in as teacher1
    When I follow "Link to learning outcomes"
    And I check "This is an informational or decorative activity"
    And I press "Save"
    Then I should see "Activity outcome tags saved"

  @javascript
  Scenario: Student can see tagged outcomes on activity page
    Given the "Test page" activity is tagged with outcome "LO1" in course "C1"
    And I am on the "Test page" "page activity" page logged in as student1
    Then I should see "This activity supports the following learning outcomes"
    And I should see "Apply bloom taxonomy"
