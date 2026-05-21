@local @local_learningoutcomes
Feature: Teacher manages course learning outcomes
  In order to align course activities to learning goals
  As an editing teacher
  I need to be able to add, edit, and delete learning outcomes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                  |
      | teacher1 | Teacher   | One      | teacher1@example.com   |
      | student1 | Student   | One      | student1@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | enabled        | 1 | local_learningoutcomes |
      | coursesdefault | 1 | local_learningoutcomes |

  @javascript
  Scenario: Teacher adds a new learning outcome
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Manage learning outcomes" in current page administration
    And I follow "Add learning outcome"
    And I set the field "Learning outcome statement" to "Students will be able to apply constructive alignment"
    And I set the field "Short name (code)" to "LO1"
    And I press "Save changes"
    Then I should see "Learning outcome added successfully"
    And I should see "LO1"
    And I should see "Students will be able to apply constructive alignment"

  @javascript
  Scenario: Teacher edits an existing learning outcome
    Given the following "local_learningoutcomes > learning outcomes" exist:
      | fullname              | shortname | course |
      | Original LO statement | LO1       | C1     |
    And I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Manage learning outcomes" in current page administration
    And I click on "Edit" "link" in the row "LO1"
    And I set the field "Learning outcome statement" to "Updated LO statement"
    And I press "Save changes"
    Then I should see "Learning outcome updated successfully"
    And I should see "Updated LO statement"

  @javascript
  Scenario: Teacher deletes a learning outcome
    Given the following "local_learningoutcomes > learning outcomes" exist:
      | fullname          | shortname | course |
      | Outcome to delete | DEL1      | C1     |
    And I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Manage learning outcomes" in current page administration
    And I click on "Delete" "link" in the row "DEL1"
    And I press "Yes"
    Then I should see "Learning outcome deleted successfully"
    And I should not see "DEL1"

  @javascript
  Scenario: Teacher cannot add a duplicate short name
    Given the following "local_learningoutcomes > learning outcomes" exist:
      | fullname      | shortname | course |
      | First outcome | DUP1      | C1     |
    And I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Manage learning outcomes" in current page administration
    And I follow "Add learning outcome"
    And I set the field "Learning outcome statement" to "Another outcome"
    And I set the field "Short name (code)" to "DUP1"
    And I press "Save changes"
    Then I should see "A learning outcome with this short name already exists in this course"

  Scenario: Student cannot access the manage outcomes page
    Given I am on the "Course 1" course page logged in as student1
    When I navigate to the manage learning outcomes page for course "C1"
    Then I should see "You do not have permission"
