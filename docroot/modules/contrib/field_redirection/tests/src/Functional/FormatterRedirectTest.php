<?php

namespace Drupal\Tests\field_redirection\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_redirection\Traits\FieldRedirectionTestTrait;

/**
 * Functionally tests formatter redirects.
 *
 * @group field_redirection
 */
class FormatterRedirectTest extends BrowserTestBase {

  use FieldRedirectionTestTrait;

  /**
   * Indicate which theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_redirection',
    'field_ui',
    'link',
    'node',
  ];

  /**
   * The test content type to add fields.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $testContentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup test content type and add a 'link' field.
    $this->testContentType = $this->setupContentTypeAndField();
  }

  /**
   * Tests basic field redirection functionality.
   *
   * Other cases are tested in FieldRedirectionResultBuilderLinkTest.
   */
  public function testFieldRedirection() {
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $this->testContentType->id(),
      'mode' => 'full',
      'status' => TRUE,
    ])->setComponent('url', ['type' => 'field_redirection_formatter'])
      ->save();

    $redirectTo = Node::create([
      'type' => $this->testContentType->id(),
      'title' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $redirectTo->save();
    $node = Node::create([
      'type' => $this->testContentType->id(),
      'title' => $this->randomMachineName(),
      'url' => [['uri' => 'entity:node/' . $redirectTo->id()]],
    ]);
    $node->save();

    // User should be redirected.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->addressEquals('node/' . $redirectTo->id());
    $this->assertSession()->pageTextNotContains('This page is set to redirect to');

    // Login as user with bypass permission, they should not be redirected.
    $this->drupalLogin($this->createUser(['bypass redirection']));
    $this->drupalGet($node->toUrl());
    $this->assertSession()->addressEquals('node/' . $node->id());
    // Message should display with a link to the destination.
    $this->assertSession()->pageTextContains('This page is set to redirect to');
    // Visiting this page also tests users with bypass permission visiting an
    // entity with no field values.
    $this->getSession()->getPage()->clickLink('another URL');
    $this->assertSession()->addressEquals('node/' . $redirectTo->id());
  }

}
