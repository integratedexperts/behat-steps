@d7
Feature: Check that FileTrait works

  @api
  Scenario: Assert "Given managed file:"
    When I am logged in as a user with the "administrator" role
    Given managed file:
      | path                                |
      | example_document.pdf                |
      | example_image.png                   |
      | example_audio.mp3                   |
      | https://via.placeholder.com/150.jpg |
    And "example_document.pdf" file object exists
    And "example_image.png" file object exists
    And "example_audio.mp3" file object exists
    And "150.jpg" file object exists
