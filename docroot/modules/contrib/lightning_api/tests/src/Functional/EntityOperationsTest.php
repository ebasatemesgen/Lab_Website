<?php

namespace Drupal\Tests\lightning_api\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that Lightning API conditionally exposes entity operations.
 *
 * @group lightning_api
 * @group orca_public
 *
 * @requires module openapi_jsonapi
 * @requires module openapi_ui_redoc
 */
class EntityOperationsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_api', 'node'];

  /**
   * Tests entity operations exposed by Lightning API.
   */
  public function testEntityOperations(): void {
    $config = $this->config('lightning_api.settings')
      ->set('entity_json', TRUE)
      ->set('bundle_docs', TRUE)
      ->save();

    $this->drupalCreateContentType(['type' => 'test']);
    $this->drupalCreateNode(['type' => 'test']);

    $this->container->get('entity_type.bundle.info')->clearCachedBundles();

    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    // Without openapi_ui_redoc and openapi_jsonapi installed, content entities
    // should be individually viewable as JSON, but not their bundle config
    // entities.
    $this->drupalGet('/admin/content');
    $this->clickLink('View JSON');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/structure/types');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('View API documentation');

    // Installing openapi_ui_redoc and openapi_jsonapi should expose a link to
    // API documentation for the bundle entity type.
    $this->container->get('module_installer')->install([
      'openapi_jsonapi',
      'openapi_ui_redoc',
    ]);

    $this->getSession()->reload();
    $this->clickLink('View API documentation');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $this->clickLink('View JSON');
    $assert_session->statusCodeEquals(200);

    // Disabling the individual entity JSON should remove the "View JSON" link.
    $config->set('entity_json', FALSE)->save();
    $this->getSession()->reload();
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('View JSON');

    // Same with the API documentation.
    $config->set('bundle_docs', FALSE)->save();
    $this->drupalGet('/admin/structure/types');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('View API documentation');
  }

}
