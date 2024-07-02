<?php

namespace Drupal\Tests\poll\Functional;

/**
 * Tests the recent poll block.
 *
 * @group poll
 */
class PollBlockTest extends PollTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array('block');

  function setUp(): void {
    parent::setUp();

    // Enable the recent poll block.
    $this->drupalPlaceBlock('poll_recent_block');
  }

  /**
   * Tests creating, viewing, voting on recent poll block.
   */
  function testRecentBlock() {

    $poll = $this->poll;
    $user = $this->web_user;

    // Verify poll appears in a block.
    $this->drupalLogin($user);
    $this->drupalGet('user');

    // If a 'block' view not generated, this title would not appear even though
    // the choices might.
    $this->assertSession()->pageTextContains($poll->label());
    $options = $poll->getOptions();
    foreach ($options as $option) {
      $this->assertSession()->pageTextContains($option);
    }

    // Verify we can vote via the block.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalGet('user/' . $this->web_user->id());
    $this->submitForm($edit, t('Vote'));
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 1');

    // Close the poll and verify block doesn't appear.
    $poll->close();
    $poll->save();
    $this->drupalGet('user/' . $user->id());
    $this->assertSession()->pageTextNotContains($poll->label());
  }
}
