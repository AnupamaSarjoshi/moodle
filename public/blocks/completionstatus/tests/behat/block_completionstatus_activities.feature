@block @block_completionstatus
Feature: Block Completion in a course details view
  In order to view the details of course completion in a course
  As a student,
  I can see the activities visible to the user.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | T1       |
      | student1 | Student   | 1        | student1@example.com | S1       |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | numsections |
      | Course 1 | C1        | 0        | 1                | 4           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity   | name   | intro                    | course | idnumber    | section | visible | completion | completionview |
      | page       | task A | page description         | C1     | page1       | 0       | 1       | 2          | 1              |
      | page       | task B | page description         | C1     | page2       | 0       | 1       | 2          | 1              |
      | assign     | task C | assignment  description  | C1     | assign1     | 1       | 1       | 2          | 1              |
      | assign     | task D | assignment description   | C1     | assign2     | 1       | 0       | 2          | 1              |
      | assign     | task E | assignment description   | C1     | assign3     | 1       | 1       | 2          | 1              |
      | assign     | task F | assignment description   | C1     | assign4     | 1       | 1       | 2          | 1              |
      | book       | task G | book description         | C1     | book1       | 2       | 1       | 2          | 1              |
      | book       | task H | book description         | C1     | book2       | 2       | 0       | 2          | 1              |
      | book       | task I | book description         | C1     | book3       | 2       | 1       | 2          | 1              |
      | book       | task J | book description         | C1     | book4       | 2       | 1       | 2          | 1              |
      | forum      | task K | forum description        | C1     | forum1      | 3       | 1       | 2          | 1              |
      | forum      | task L | forum description        | C1     | forum2      | 3       | 0       | 2          | 1              |
      | forum      | task M | forum description        | C1     | forum3      | 3       | 1       | 2          | 1              |
      | forum      | task N | forum description        | C1     | forum4      | 3       | 1       | 2          | 1              |
      | choice     | task O | choice description       | C1     | choice1     | 4       | 1       | 2          | 1              |
      | choice     | task P | choice description       | C1     | choice2     | 4       | 0       | 2          | 1              |
      | choice     | task Q | choice description       | C1     | choice3     | 4       | 1       | 2          | 1              |
      | choice     | task R | choice description       | C1     | choice4     | 4       | 1       | 2          | 1              |

  @javascript
  Scenario: As a student, I see all the user visible activities in the detailed view of a course completion status block
    Given I am on the "Course 1" course page logged in as teacher1
    And I change window size to "large"
    And I turn editing mode on
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets

    # Set course completion criteria for the activities to be completed.
    And I set the field "Page - task A" to "1"
    And I set the field "Page - task B" to "1"
    And I set the field "Assignment - task C" to "1"
    And I set the field "Assignment - task D" to "1"
    And I set the field "Assignment - task E" to "1"
    And I set the field "Assignment - task F" to "1"
    And I set the field "Book - task G" to "1"
    And I set the field "Book - task H" to "1"
    And I set the field "Book - task I" to "1"
    And I set the field "Book - task J" to "1"
    And I set the field "Forum - task K" to "1"
    And I set the field "Forum - task L" to "1"
    And I set the field "Forum - task M" to "1"
    And I set the field "Forum - task N" to "1"
    And I set the field "Choice - task O" to "1"
    And I set the field "Choice - task P" to "1"
    And I set the field "Choice - task Q" to "1"
    And I set the field "Choice - task R" to "1"
    And I press "Save changes"

    # Add activity completion restrictions.
    And I am on the "task E" "assign activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"
    And I am on the "task F" "assign activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"

    And I am on the "task I" "book activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"
    And I am on the "task J" "book activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"

    # Hide section 2 to make sure book activities are not visible to the student.
    And I turn editing mode on
    And I hide section "2"

    And I am on the "task M" "forum activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"
    And I am on the "task N" "forum activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"

    # Add restrictions to section 3.
    And I turn editing mode on
    And I edit the section "3"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task A"
    And I press "Save changes"

    And I am on the "task Q" "choice activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"
    And I am on the "task R" "choice activity editing" page logged in as admin
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Activity or resource" to "task B"
    And I press "Save and return to course"

    # Add restrictions to section 4.
    And I turn editing mode on
    And I edit the section "4"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task A"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save changes"
    And I log out

    When I am on the "Course 1" course page logged in as student1
    Then I should see "Status: Not yet started"
    And I should see "0 of 4" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A"
    And I should see "task B"
    And I should see "task C"
    And I should not see "task D"
    And I should see "task E"
    And I should not see "task F"
    And I should not see "task G"
    And I should not see "task H"
    And I should not see "task I"
    And I should not see "task J"
    And I should not see "task K"
    And I should not see "task L"
    And I should not see "task M"
    And I should not see "task N"
    And I should not see "task O"
    And I should not see "task P"
    And I should not see "task Q"
    And I should not see "task R"
    And I click on "task A" "link"

    And I am on the "Course 1" course page logged in as student1
    And I should see "Status: In progress"
    And I should see "1 of 8" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A"
    And I should see "task B"
    And I should see "task C"
    And I should not see "task D"
    And I should see "task E"
    And I should not see "task F"
    And I should not see "task G"
    And I should not see "task H"
    And I should not see "task I"
    And I should not see "task J"
    And I should see "task K"
    And I should not see "task L"
    And I should see "task M"
    And I should not see "task N"
    And I should see "task O"
    And I should not see "task P"
    And I should see "task Q"
    And I should not see "task R"
    And I click on "task B" "link"

    And I am on the "Course 1" course page logged in as student1
    And I should see "Status: In progress"
    And I should see "2 of 11" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A"
    And I should see "task B"
    And I should see "task C"
    And I should not see "task D"
    And I should see "task E"
    And I should see "task F"
    And I should not see "task G"
    And I should not see "task H"
    And I should not see "task I"
    And I should not see "task J"
    And I should see "task K"
    And I should not see "task L"
    And I should see "task M"
    And I should see "task N"
    And I should see "task O"
    And I should not see "task P"
    And I should see "task Q"
    And I should see "task R"

  @javascript
  Scenario: The detailed course completion view should only show activities with completion tracking enabled.
    Given I am on the "Course 1" course page logged in as teacher1
    And I change window size to "large"
    And I turn editing mode on
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets

    # Set completion of the activities.
    And I set the field "Page - task A" to "1"
    And I set the field "Page - task B" to "1"
    And I press "Save changes"

    # Disable completion tracking for "task A".
    And I am on the "task A" "page activity editing" page logged in as admin
    And I expand all fieldsets
    And I set the following fields to these values:
      | completion | 0 |
    And I press "Save and return to course"

    When I am on the "Course 1" course page logged in as student1
    And I should see "Status: Not yet started"
    And I should see "0 of 1" in the "Activity completion" "table_row"
    And I follow "More details"
    Then I should not see "task A" in the "criteriastatus" "table"
    And I should see "task B" in the "criteriastatus" "table"
