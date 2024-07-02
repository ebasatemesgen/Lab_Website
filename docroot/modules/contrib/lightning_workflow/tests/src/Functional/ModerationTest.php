<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic content moderation operations.
 *
 * @group lightning_workflow
 * @group orca_public
 */
class ModerationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_workflow',
    'path',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'moderated',
      'third_party_settings' => [
        'lightning_workflow' => [
          'workflow' => 'editorial',
        ],
      ],
    ]);
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalCreateNode([
      'type' => 'moderated',
      'title' => 'Alpha',
      'moderation_state' => 'review',
      'promote' => TRUE,
    ]);
    $this->drupalCreateNode([
      'type' => 'moderated',
      'title' => 'Beta',
      'moderation_state' => 'published',
      'promote' => TRUE,
    ]);
    $this->drupalCreateNode([
      'type' => 'moderated',
      'title' => 'Charlie',
      'moderation_state' => 'draft',
      'promote' => FALSE,
    ]);
  }

  /**
   * Tests publishing moderated content.
   */
  public function testPublish() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access content overview',
      'create moderated content',
      'create url aliases',
      'edit any moderated content',
      'use editorial transition publish',
      'use editorial transition review',
      'view any unpublished content',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $page->clickLink('Alpha');
    $assert_session->elementExists('named', ['link', 'edit-form'])->click();
    $page->selectFieldOption('moderation_state[0][state]', 'Published');
    $page->pressButton('Save');
    $this->drupalLogout();
    $this->drupalGet('/node');
    $assert_session->linkExists('Alpha');
  }

  /**
   * Tests unpublishing moderated content.
   */
  public function testUnpublish() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access content overview',
      'create moderated content',
      'create url aliases',
      'edit any moderated content',
      'use editorial transition archive',
      'use editorial transition publish',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $page->clickLink('Beta');
    $assert_session->elementExists('named', ['link', 'edit-form'])->click();
    $page->selectFieldOption('moderation_state[0][state]', 'Archived');
    $page->pressButton('Save');
    $this->drupalLogout();
    $this->drupalGet('/node');
    $assert_session->linkNotExists('Beta');
  }

}
