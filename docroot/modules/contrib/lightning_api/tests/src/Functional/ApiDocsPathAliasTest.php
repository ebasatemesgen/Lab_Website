<?php

namespace Drupal\Tests\lightning_api\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning_api
 *
 * @requires module openapi_jsonapi
 * @requires module openapi_ui_redoc
 */
class ApiDocsPathAliasTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'openapi_ui_redoc',
    'openapi_jsonapi',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->createUser(['access openapi api docs']);
    $this->drupalLogin($account);
  }

  /**
   * Tests that Lightning API creates an `/api-docs` alias on install.
   */
  public function testPathAlias(): void {
    $this->container->get('module_installer')->install(['lightning_api']);
    $this->drupalGet('/api-docs');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that an existing `/api-docs` alias is not changed.
   */
  public function testExistingAliasNotOverwritten(): void {
    $node_type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode([
      'type' => $node_type->id(),
      'path' => '/api-docs',
    ]);

    $this->drupalGet($node->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals('/api-docs');

    $this->container->get('module_installer')->install(['lightning_api']);
    $this->getSession()->reload();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($node->getTitle());
  }

  /**
   * Data provider for ::testOpenApiModulesMustBeInstalled().
   *
   * @return array[]
   *   The test cases.
   */
  public function providerOpenApiModulesMustBeInstalled(): array {
    return [
      ['openapi_ui_redoc'],
      ['openapi_jsonapi'],
    ];
  }

  /**
   * Tests the alias isn't created if OpenAPI modules aren't installed.
   *
   * @param string $module_to_uninstall
   *   The name of the module to uninstall before installing Lightning API.
   *
   * @dataProvider providerOpenApiModulesMustBeInstalled
   */
  public function testOpenApiModulesMustBeInstalled(string $module_to_uninstall): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->uninstall([$module_to_uninstall]);
    $module_installer->install(['lightning_api']);

    $this->drupalGet('/api-docs');
    $this->assertSession()->statusCodeEquals(404);
  }

}
