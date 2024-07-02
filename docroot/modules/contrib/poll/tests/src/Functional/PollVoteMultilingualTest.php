<?php

namespace Drupal\Tests\poll\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\poll\Entity\Poll;


/**
 * Tests multilingual voting on a poll.
 *
 * @group poll
 */
class PollVoteMultilingualTest extends PollTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $adminPermissions = [
    'administer content translation',
    'administer languages',
    'create content translations',
    'update content translations',
    'translate any entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow anonymous users to vote on polls.
    user_role_change_permissions(AccountInterface::ANONYMOUS_ROLE, array(
      'cancel own vote' => TRUE,
      'access polls' => TRUE,
    ));

    $this->poll = $this->pollCreate(3);

    $this->poll->setAnonymousVoteAllow(TRUE)->save();
  }

  /**
   * Tests multilingual voting on a poll.
   */
  public function testPollVoteMultilingual() {

    $this->drupalLogin($this->admin_user);

    // Add another language.
    $language = ConfigurableLanguage::createFromLangcode('ca');
    $language->save();

    // Make poll translatable.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = array(
      'entity_types[poll]' => TRUE,
      'entity_types[poll_choice]' => TRUE,
      'settings[poll][poll][translatable]' => TRUE,
      'settings[poll_choice][poll_choice][translatable]' => TRUE,
    );
    $this->submitForm($edit, t('Save configuration'));
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Translate a poll.
    $this->drupalGet('poll/' . $this->poll->id() . '/translations');
    $this->clickLink(t('Add'));
    $edit = array(
      'question[0][value]' => 'ca question',
      'choice[0][choice]' => 'ca choice 1',
      'choice[1][choice]' => 'ca choice 2',
      'choice[2][choice]' => 'ca choice 3',
    );
    $this->submitForm($edit, t('Save'));
    $this->drupalGet('ca/poll/' . $this->poll->id());
    $this->assertSession()->pageTextContains('ca choice 1');

    \Drupal::entityTypeManager()->getStorage('poll')->resetCache();
    \Drupal::entityTypeManager()->getStorage('poll_choice')->resetCache();
    $this->poll = Poll::load($this->poll->id());

    // Login as web user.
    $this->drupalLogin($this->web_user);

    // Record a vote.
    $edit = array(
      'choice' => $this->getChoiceId($this->poll, 2),
    );
    $this->drupalGet('poll/' . $this->poll->id());
    $this->submitForm($edit, t('Vote'));
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 1');

    $this->drupalGet('ca/poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(isset($elements[0]), "'Cancel vote' button appears.");
    $this->drupalGet('poll/' . $this->poll->id());

    // Cancel a vote.
    $this->submitForm(array(), t('Cancel vote'));
    $this->assertSession()->pageTextContains('Your vote was cancelled.');
    $this->assertSession()->pageTextNotContains('Cancel your vote');

    // Vote again in reverse order.
    $edit = array(
      'choice' => $this->getChoiceIdByLabel($this->poll->getTranslation('ca'), 'ca choice 2'),
    );
    $this->drupalGet('ca/poll/' . $this->poll->id());
    $this->submitForm($edit, t('Vote'));
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 1');

    $this->drupalGet('poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(isset($elements[0]), "'Cancel vote' button appears.");

    // Edit the original poll.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('poll/' . $this->poll->id() . '/edit');
    $edit = array(
      'choice[0][choice]' => '',
      'choice[1][choice]' => 'choice 2',
      'choice[2][choice]' => 'choice 3',
      'choice[3][choice]' => 'choice 4',
    );
    $this->submitForm($edit, t('Save'));

    // Translate the new label.
    $this->drupalGet('ca/poll/' . $this->poll->id() . '/edit');
    $edit = array(
      'choice[2][choice]' => 'ca choice 4',
    );
    $this->submitForm($edit, t('Save'));

    \Drupal::entityTypeManager()->getStorage('poll')->resetCache();
    \Drupal::entityTypeManager()->getStorage('poll_choice')->resetCache();
    $this->poll = Poll::load($this->poll->id());

    // Vote as anonymous user.
    $this->drupalLogout();
    $edit = array(
      'choice' => $this->getChoiceIdByLabel($this->poll->getTranslation('ca'), 'ca choice 4'),
    );
    $this->drupalGet('ca/poll/' . $this->poll->id());
    $this->submitForm($edit, t('Vote'));
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 2');
    $this->assertSession()->pageTextNotContains('ca choice 1');
    $this->assertSession()->pageTextContains('ca choice 4');
    $elements = $this->xpath('//*[@id="poll-view-form-2"]/div[1]/dl/dd[1]')[0];
    $this->assertEquals($elements->getText(), '50% (1 vote)');
    $elements = $this->xpath('//*[@id="poll-view-form-2"]/div[1]/dl/dd[3]')[0];
    $this->assertEquals($elements->getText(), '50% (1 vote)');

    $this->drupalGet('poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(isset($elements[0]), "'Cancel vote' button appears.");
    $this->assertSession()->pageTextContains('Total votes: 2');
    $elements = $this->xpath('//*[@id="poll-view-form-2"]/div[1]/dl/dd[1]')[0];
    $this->assertEquals($elements->getText(), '50% (1 vote)');
    $elements = $this->xpath('//*[@id="poll-view-form-2"]/div[1]/dl/dd[3]')[0];
    $this->assertEquals($elements->getText(), '50% (1 vote)');
  }

}
