@local @local_learningoutcomes
Feature: Teacher views the learning outcomes alignment report
  In order to identify coverage gaps in my course
  As a teacher
  I need to see a report of outcomes without activities and activities without outcomes

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
      | activity | course | name       |
      | page     | C1     | Page one   |
      | page     | C1     | Page two   |
    And the following "local_learningoutcomes > learning outcomes" exist:
      | fullname             | shortname | course |
      | Apply bloom taxonomy | LO1       | C1     |
      | Analyse case studies | LO2       | C1     |
    And the following config values are set as admin:
      | enabled        | 1 | local_learningoutcomes |
      | coursesdefault | 1 | local_learningoutcomes |

  @javascript
  Scenario: Alignment report shows outcomes without activities
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Learning outcomes alignment" in current page administration
    Then I should see "Learning outcomes alignment"
    And I should see "LO1"
    And I should see "LO2"

  @javascript
  Scenario: Alignment report shows untagged activities with tag links
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Learning outcomes alignment" in current page administration
    Then I should see "Activities without learning outcomes"
    And I should see "Page one"
    And I should see "Page two"
    And the page should contain a link "Tag this activity" for "Page one"

  @javascript
  Scenario: Alignment report shows 100% score when all activities are tagged
    Given the "Page one" activity is tagged with outcome "LO1" in course "C1"
    And the "Page two" activity is tagged with outcome "LO2" in course "C1"
    And I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Learning outcomes alignment" in current page administration
    Then I should see "100%"
    And I should not see "Activities without learning outcomes"

  @javascript
  Scenario: Student cannot access the alignment report
    Given I am on the "Course 1" course page logged in as student1
    When I navigate to "Learning outcomes alignment" in current page administration
    Then I should see "You do not have permission"

  @javascript
  Scenario: Alignment report links back to manage outcomes when none defined
    Given the following "courses" exist:
      | fullname  | shortname | category |
      | Empty C2  | C2        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C2     | editingteacher |
    When I am on the "Empty C2" course page logged in as teacher1
    And I navigate to "Learning outcomes alignment" in current page administration
    Then I should see "No learning outcomes have been defined for this course"
    And I should see "Add learning outcomes"
