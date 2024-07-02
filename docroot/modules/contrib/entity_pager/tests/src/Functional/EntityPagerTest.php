<?php

namespace Drupal\Tests\entity_pager\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the entity pager view style.
 *
 * @group entity_pager
 */
class EntityPagerTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_pager',
    'entity_pager_test_views',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_relationship_pager'];

  /**
   * The nodes used in tests.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $view = View::load('entity_pager_example');
    $view->setStatus(TRUE)->save();

    $type = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $type])->save();

    $test_user = $this->drupalCreateUser();

    $now = $this->container
      ->get('datetime.time')
      ->getRequestTime();
    for ($i = 0; $i < 5; $i++) {
      $node = Node::create([
        'type' => $type,
        'title' => $this->randomMachineName(),
        // Ensure created times are in sequence.
        'created' => $now + ($i * 10),
        // Set different author for some nodes for ::testRelationship().
        'uid' => $i < 3 ? $test_user->id() : NULL,
      ]);
      $node->save();
      $this->nodes[] = $node;
    }
  }

  /**
   * Tests paging links.
   */
  public function testPagingLinks() {
    $this->drupalPlaceBlock('views_block:entity_pager_example-entity_pager_example_block', ['id' => 'test']);
    $this->drupalGet($this->nodes[1]->toUrl());

    $elements = $this->cssSelect('#block-test .entity-pager-item-prev a');
    $this->assertCount(1, $elements, 'Previous link exists.');
    $link = reset($elements);
    $expected = $this->nodes[0]->toUrl()->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Previous link points to previous view row.');
    $this->assertEquals('< prev', trim($link->getText()), 'Previous link text from configuration.');

    $elements = $this->cssSelect('#block-test .entity-pager-item-next a');
    $this->assertCount(1, $elements, 'Next link exists.');
    $link = reset($elements);
    $expected = $this->nodes[2]->toUrl()->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Next link points to next view row.');
    $this->assertEquals('next >', trim($link->getText()), 'Next link text from configuration.');

    $this->updateExampleView([
      'link_prev' => '<i>Text</i>',
      'link_next' => '<b>Text</b>',
    ]);
    $this->drupalGet($this->getUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-prev a > i');
    $this->assertCount(1, $elements, 'HTML is displayed in previous link text.');
    $elements = $this->cssSelect('#block-test .entity-pager-item-next a > b');
    $this->assertCount(1, $elements, 'HTML is displayed in next link text.');

    $this->updateExampleView([
      'link_prev' => '[site:name]',
      'link_next' => '[site:name]',
    ]);
    $this->drupalGet($this->getUrl());
    $expected = \Drupal::token()->replace('[site:name]');
    $link = $this->cssSelect('#block-test .entity-pager-item-prev a')[0];
    $this->assertEquals($expected, trim($link->getText()), 'Global token replacement for previous link.');
    $link = $this->cssSelect('#block-test .entity-pager-item-next a')[0];
    $this->assertEquals($expected, trim($link->getText()), 'Global token replacement for next link.');

    $this->updateExampleView([
      'link_prev' => 'Node [node:nid]',
      'link_next' => 'Node [node:nid]',
    ]);
    $this->drupalGet($this->getUrl());
    $link = $this->cssSelect('#block-test .entity-pager-item-prev a')[0];
    $this->assertEquals('Node 1', trim($link->getText()), 'Node context token replacement for previous link.');
    $link = $this->cssSelect('#block-test .entity-pager-item-next a')[0];
    $this->assertEquals('Node 3', trim($link->getText()), 'Node context token replacement for next link.');

    $this->updateExampleView([
      'link_prev' => 'Node [node:nid] [node:_invalid_token_]',
      'link_next' => 'Node [node:nid] [node:_invalid_token_]',
    ]);
    $this->drupalGet($this->getUrl());
    $link = $this->cssSelect('#block-test .entity-pager-item-prev a')[0];
    $this->assertEquals('Node 1', trim($link->getText()), 'Node context token replacement for previous link clearing invalid tokens.');
    $link = $this->cssSelect('#block-test .entity-pager-item-next a')[0];
    $this->assertEquals('Node 3', trim($link->getText()), 'Node context token replacement for next link clearing invalid tokens.');

    $first = reset($this->nodes);
    $last = end($this->nodes);

    $this->drupalGet($first->toUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-prev span.inactive');
    $this->assertCount(1, $elements, 'Previous link inactive substitute exists.');

    $this->drupalGet($last->toUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-next span.inactive');
    $this->assertCount(1, $elements, 'Next link inactive substitute exists.');

    $this->updateExampleView(['show_disabled_links' => FALSE]);

    $this->drupalGet($first->toUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-prev');
    $this->assertEmpty($elements, 'Previous link on first page does not exist with show_disabled_links disabled.');

    $this->drupalGet($last->toUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-next');
    $this->assertEmpty($elements, 'Next link on last page does not exist with show_disabled_links disabled.');

    $this->updateExampleView(['circular_paging' => TRUE]);

    $this->drupalGet($first->toUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-prev a');
    $this->assertCount(1, $elements, 'Previous link exists.');
    $link = reset($elements);
    $expected = $last->toUrl()->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Previous link with circular paging on first page links to last result.');

    $this->drupalGet($last->toUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-next a');
    $this->assertCount(1, $elements, 'Next link exists.');
    $link = reset($elements);
    $expected = $first->toUrl()->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Next link with circular paging on last page links to first result.');
  }

  /**
   * Tests all link.
   */
  public function testAllLink() {
    $this->drupalPlaceBlock('views_block:entity_pager_example-entity_pager_example_block', ['id' => 'test']);
    $this->drupalGet($this->nodes[1]->toUrl());

    $elements = $this->cssSelect('#block-test .entity-pager-item-all a');
    $this->assertCount(1, $elements, 'All link exists.');
    $link = reset($elements);
    $expected = Url::fromUserInput('/')->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'All link points to URI set in configuration.');
    $this->assertEquals('Home', trim($link->getText()), 'All link text from configuration.');

    $this->updateExampleView(['link_all_text' => '<b>Text</b>']);
    $this->drupalGet($this->getUrl());
    $elements = $this->cssSelect('#block-test .entity-pager-item-all a > b');
    $this->assertCount(1, $elements, 'HTML is displayed in all text.');

    $this->updateExampleView([
      'link_all_text' => '[site:name]',
      'link_all_url' => '[site:name]',
    ]);
    $this->drupalGet($this->getUrl());
    $link = $this->cssSelect('#block-test .entity-pager-item-all a')[0];
    $expected = \Drupal::token()->replace('[site:name]');
    $this->assertEquals($expected, trim($link->getText()), 'Global token text replacement.');
    $expected = Url::fromUserInput("/$expected")->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Global token URL replacement.');

    $this->updateExampleView([
      'link_all_text' => '[node:nid]',
      'link_all_url' => '[node:nid]',
    ]);
    $this->drupalGet($this->getUrl());
    $link = $this->cssSelect('#block-test .entity-pager-item-all a')[0];
    $expected = (string) $this->nodes[1]->id();
    $this->assertEquals($expected, trim($link->getText()), 'Node context token text replacement.');
    $expected = Url::fromUserInput("/$expected")->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Node context token URL replacement.');

    $this->updateExampleView([
      'link_all_text' => '[node:nid] [node:_invalid_token_]',
      'link_all_url' => '[node:nid][node:_invalid_token_]',
    ]);
    $this->drupalGet($this->getUrl());
    $link = $this->cssSelect('#block-test .entity-pager-item-all a')[0];
    $expected = (string) $this->nodes[1]->id();
    $this->assertEquals($expected, trim($link->getText()), 'Node context token text replacement clearing invalid tokens.');
    $expected = Url::fromUserInput("/$expected")->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Node context token URL replacement clearing invalid tokens.');

    $this->updateExampleView(['link_all_url' => '/a%20space']);
    $this->drupalGet($this->getUrl());
    $href = $this->cssSelect('#block-test .entity-pager-item-all a')[0]->getAttribute('href');
    $this->assertEquals(base_path() . 'a%20space', $href, 'URLs should not be double encoded.');

    $this->updateExampleView(['link_all_url' => 'https://example.com']);
    $this->drupalGet($this->getUrl());
    $link = $this->cssSelect('#block-test .entity-pager-item-all a')[0];
    $this->assertEquals('https://example.com', $link->getAttribute('href'), 'External URL.');

    $this->updateExampleView(['display_all' => FALSE]);
    $this->drupalGet($this->getUrl());
    $this->assertEmpty($this->cssSelect('#block-test .entity-pager-item-all'), 'All link does not exist.');
  }

  /**
   * Tests the count markup.
   */
  public function testsCount() {
    $this->drupalPlaceBlock('views_block:entity_pager_example-entity_pager_example_block', ['id' => 'test']);
    $this->drupalGet($this->nodes[1]->toUrl());

    $assert = $this->assertSession();

    // Assert result count is displayed.
    $assert->pageTextContains('2 of 5');

    // Assert toggling 'display_count' removes count text.
    $this->updateExampleView(['display_count' => FALSE]);
    $this->drupalGet($this->getUrl());
    $assert->pageTextNotContains('2 of 5');
  }

  /**
   * Tests pager with relationships.
   */
  public function testRelationship() {
    ViewTestData::createTestViews(self::class, ['entity_pager_test_views']);
    $this->drupalPlaceBlock('views_block:test_relationship_pager-block_1', ['id' => 'test']);
    $this->drupalGet($this->nodes[1]->toUrl());

    $elements = $this->cssSelect('#block-test .entity-pager-item-prev a');
    $this->assertCount(1, $elements, 'Previous link exists.');
    $link = reset($elements);
    $expected = $this->nodes[0]->toUrl()->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Previous link points to previous view row.');

    $elements = $this->cssSelect('#block-test .entity-pager-item-next a');
    $this->assertCount(1, $elements, 'Next link exists.');
    $link = reset($elements);
    $expected = $this->nodes[2]->toUrl()->toString();
    $this->assertEquals($expected, $link->getAttribute('href'), 'Next link points to next view row.');

    $link = $this->cssSelect('#block-test .entity-pager-item-all a')[0];
    $expected = (string) $this->nodes[1]->id();
    $this->assertEquals("Node $expected", trim($link->getText()), 'Node context token text replacement.');
  }

  /**
   * Tests handling on non-entity pages.
   *
   * @see https://www.drupal.org/project/entity_pager/issues/3111423
   */
  public function testNonEntityPage() {
    $this->drupalPlaceBlock('views_block:entity_pager_example-entity_pager_example_block');
    $this->drupalGet('user/login');
  }

  /**
   * Update style settings for the example view.
   *
   * @param array $options
   *   Style options to update.
   *
   * @return \Drupal\views\ViewExecutable
   *   The updated view executable.
   */
  protected function updateExampleView(array $options) {
    $view = Views::getView('entity_pager_example');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['style']['options'] = $options + $display['display_options']['style']['options'];
    $view->save();

    return $view;
  }

}
