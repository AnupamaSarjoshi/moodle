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
    And I am on the "Course 1" course page logged in as teacher1
    And I change window size to "large"
    And I turn editing mode on

  @javascript
  Scenario: Student visibility respects combined activity and section restrictions with progressive completion
    Given the following "activities" exist:
      | activity | name   | intro                  | course | idnumber | section | visible | completion | completionview |
      | page     | task A | page description       | C1     | page1    | 0       | 1       | 2          | 1              |
      | page     | task B | page description       | C1     | page2    | 1       | 1       | 2          | 1              |
      | assign   | task C | assignment description | C1     | assign1  | 1       | 1       | 2          | 1              |
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Page - task A       | 1 |
      | Page - task B       | 1 |
      | Assignment - task C | 1 |
    And I press "Save changes"

    # Add conditionally visible restriction (open eye) to section 1 requiring task A completion.
    And I turn editing mode on
    And I edit the section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task A"
    And I press "Save changes"

    # Add conditionally hidden restriction (closed eye) to task C requiring task A completion.
    And I am on the "task C" "assign activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Activity or resource" to "task A"
    And I press "Save and return to course"
    And I log out

    # Initial state: Only unrestricted visible activities appear.
    When I am on the "Course 1" course page logged in as student1
    Then I should see "Status: Not yet started"
    And I should see "0 of 1" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A" in the "criteriastatus" "table"
    And I should not see "task B" in the "criteriastatus" "table"
    And I should not see "task C" in the "criteriastatus" "table"

    # After completing task A: Section 2 activities become visible.
    And I click on "task A" "link"
    And I am on the "Course 1" course page logged in as student1
    And I should see "Status: In progress"
    And I should see "1 of 3" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A" in the "criteriastatus" "table"
    And I should see "task B" in the "criteriastatus" "table"
    And I should see "task C" in the "criteriastatus" "table"

  @javascript
  Scenario: Student completion view shows only accessible activities considering all activity restrictions
    Given the following "activities" exist:
      | activity | name   | intro                  | course | idnumber | section | visible | completion | completionview |
      | page     | task A | page description       | C1     | page1    | 0       | 1       | 2          | 1              |
      | page     | task B | page  description      | C1     | assign1  | 1       | 1       | 2          | 1              |
      | assign   | task C | assignment description | C1     | assign2  | 1       | 1       | 2          | 1              |
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets

    # Set completion of the activities.
    And I set the following fields to these values:
      | Page - task A       | 1 |
      | Page - task B       | 1 |
      | Assignment - task C | 1 |
    And I press "Save changes"

    # Add conditionally visible restriction (open eye) to "task B".
    And I am on the "task B" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task A"
    And I press "Save and return to course"

    # Add conditionally hidden restriction (closed eye) to "task C".
    And I am on the "task C" "assign activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the field "Activity or resource" to "task A"
    And I press "Save and return to course"

    When I am on the "Course 1" course page logged in as student1
    And I should see "Status: Not yet started"
    And I should see "0 of 2" in the "Activity completion" "table_row"
    And I follow "More details"
    Then I should see "task A" in the "criteriastatus" "table"
    And I should see "task B" in the "criteriastatus" "table"
    And I should not see "task C" in the "criteriastatus" "table"
    And I click on "task A" "link"

    # Complete task A to make task C visible.
    And I am on the "Course 1" course page logged in as student1
    And I should see "Status: In progress"
    And I should see "1 of 3" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A" in the "criteriastatus" "table"
    And I should see "task B" in the "criteriastatus" "table"
    And I should see "task C" in the "criteriastatus" "table"

  @javascript
  Scenario: Hidden activities do not appear in the completion status block
    Given the following "activities" exist:
      | activity | name   | intro            | course | idnumber    | section | visible | completion | completionview |
      | page     | task A | page description | C1     | page1       | 0       | 1       | 2          | 1              |
      | page     | task B | page description | C1     | page2       | 0       | 0       | 2          | 1              |
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets

    # Set completion of the activities.
    And I set the following fields to these values:
      | Page - task A | 1 |
      | Page - task B | 1 |
    And I press "Save changes"

    When I am on the "Course 1" course page logged in as student1
    And I should see "Status: Not yet started"
    And I should see "0 of 1" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A" in the "criteriastatus" "table"
    Then I should not see "task B" in the "criteriastatus" "table"

  @javascript
  Scenario: Activities in the hidden section do not appear in the completion status block
    Given the following "activities" exist:
      | activity | name   | intro            | course | idnumber | section | visible | completion | completionview |
      | page     | task A | page description | C1     | page1    | 0       | 1       | 2          | 1              |
      | page     | task B | page description | C1     | page2    | 1       | 1       | 2          | 1              |
      | assign   | task C | page description | C1     | page3    | 2       | 1       | 2          | 1              |
      | assign   | task D | page description | C1     | page4    | 3       | 1       | 2          | 1              |
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets

    # Set completion of the activities.
    And I set the following fields to these values:
      | Page - task A       | 1 |
      | Page - task B       | 1 |
      | Assignment - task C | 1 |
      | Assignment - task D | 1 |
    And I press "Save changes"

    # Hide section 1 to make sure book activities are not visible to the student.
    And I hide section "1"

    # Add conditionally visible restriction to section 2.
    And I edit the section "2"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task A"
    And I press "Save changes"

    # Add conditionally hidden restriction to section 3.
    And I edit the section "3"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "task A"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save changes"
    And I log out

    When I am on the "Course 1" course page logged in as student1
    And I should see "Status: Not yet started"
    And I should see "0 of 1" in the "Activity completion" "table_row"
    And I follow "More details"
    Then I should see "task A" in the "criteriastatus" "table"
    And I should not see "task B" in the "criteriastatus" "table"
    And I should not see "task C" in the "criteriastatus" "table"
    And I should not see "task D" in the "criteriastatus" "table"

    # Complete task A to make other activities visible.
    And I click on "task A" "link"
    And I am on the "Course 1" course page logged in as student1
    And I should see "Status: In progress"
    And I should see "1 of 3" in the "Activity completion" "table_row"
    And I follow "More details"
    And I should see "task A" in the "criteriastatus" "table"
    And I should not see "task B" in the "criteriastatus" "table"
    And I should see "task C" in the "criteriastatus" "table"
    And I should see "task D" in the "criteriastatus" "table"

  @javascript
  Scenario: Activities with disabled completion tracking are omitted from the completion view
    Given the following "activities" exist:
      | activity | name   | intro            | course | idnumber | section | visible | completion | completionview |
      | page     | task A | page description | C1     | page1    | 0       | 1       | 2          | 1              |
      | page     | task B | page description | C1     | page2    | 1       | 1       | 2          | 1              |
    And I add the "Course completion status" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets

    # Set completion of the activities.
    And I set the following fields to these values:
      | Page - task A | 1 |
      | Page - task B | 1 |
    And I press "Save changes"

    # Disable completion tracking for "task A".
    And I am on the "task A" "page activity editing" page
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
