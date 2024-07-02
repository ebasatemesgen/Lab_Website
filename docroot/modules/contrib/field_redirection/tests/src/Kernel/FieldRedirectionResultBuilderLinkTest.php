<?php

namespace Drupal\Tests\field_redirection\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\field_redirection\FieldRedirectionResult;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for testing field redirection result builder for link fields.
 *
 * @group field_redirection
 */
class FieldRedirectionResultBuilderLinkTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'field_redirection',
    'link',
    'path',
    'system',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig('user');
    $storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_redirect_link',
      'id' => 'entity_test.field_redirect_link',
      'type' => 'link',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $storage->save();

    $config = FieldConfig::create([
      'field_name' => 'field_redirect_link',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'id' => 'entity_test.entity_test.field_redirect_link',
      'label' => 'Redirect',
    ]);
    $config->save();
    \Drupal::configFactory()->getEditable('system.site')->set('name', 'field-redirection-test')->save();
    \Drupal::state()->set('system.cron_key', '12345678');

    // Create user 1 so our tests don't use it.
    $this->createUser();
  }

  /**
   * Tests the 404_on_empty setting.
   */
  public function testFieldRedirectionResult404OnEmpty() {
    $entity = $this->createTestEntity();
    $builder = \Drupal::service('field_redirection.result_builder');
    $request = Request::create('/');
    $this->expectException(NotFoundHttpException::class);
    $builder->buildResult($entity->get('field_redirect_link'), $request, $this->createUser([]), ['404_if_empty' => TRUE]);
  }

  /**
   * Tests the maintenance mode.
   */
  public function testFieldRedirectionResultMaintenanceMode() {
    $entity = $this->createTestEntity(['field_redirect_link' => ['uri' => 'http://example.com']]);
    $builder = \Drupal::service('field_redirection.result_builder');
    $request = Request::create('/');
    \Drupal::state()->set('system.maintenance_mode', 1);
    $this->assertEquals(FieldRedirectionResult::deny(), $builder->buildResult($entity->get('field_redirect_link'), $request, $this->createUser([])));
  }

  /**
   * Tests builder.
   *
   * @dataProvider providerTestFieldRedirectionResultBuilder
   */
  public function testFieldRedirectionResultBuilderDenyStates($field_values = [], $user_permissions = [], $current_path = '/user', $current_route = 'user.page', array $settings = [], callable $request_callback = NULL) {
    $entity = $this->createTestEntity($field_values);
    $builder = \Drupal::service('field_redirection.result_builder');
    $request = Request::create($current_path);
    if ($current_route) {
      $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $current_route);
      $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route($current_path));
    }
    if ($request_callback) {
      $request_callback($request);
    }
    \Drupal::requestStack()->push($request);
    $this->assertEquals(FieldRedirectionResult::deny(), $builder->buildResult($entity->get('field_redirect_link'), $request, $this->createUser($user_permissions), $settings));
  }

  /**
   * Data provider for ::testFieldRedirectionResultBuilderDenyStates().
   *
   * @return array
   *   Test cases.
   */
  public function providerTestFieldRedirectionResultBuilder() {
    $default_field_values = ['field_redirect_link' => ['uri' => 'http://example.com']];
    $request = Request::create('/');
    return [
      'non matching page, exclude' => [
        $default_field_values,
        [],
        '/user',
        'user.page',
        ['page_restrictions' => '2', 'pages' => '/user'],
      ],
      'non matching page, include' => [
        $default_field_values,
        [],
        '/user',
        'user.page',
        ['page_restrictions' => '1', 'pages' => '/node'],
      ],
      'non matching page, include w/ tokens' => [
        $default_field_values,
        [],
        '/user',
        'user.page',
        ['page_restrictions' => '1', 'pages' => '/[site:name]'],
      ],
      'non matching page, exclude w/ tokens' => [
        $default_field_values,
        [],
        '/field-redirection-test',
        '<front>',
        ['page_restrictions' => '2', 'pages' => '/[site:name]'],
      ],
      'cron run from external' => [
        $default_field_values,
        [],
        '/cron/1231234',
        'system.cron',
        [],
      ],
      'manual cron run' => [
        $default_field_values,
        [],
        '/admin/reports/status/run-cron',
        'system.run_cron',
        [],
      ],
      'empty field, not 404 on empty' => [
        [],
        [],
        '/user',
        'user.page',
        ['404_if_empty' => FALSE],
      ],
      'empty field, 404 on empty, but bypass permission' => [
        [],
        ['bypass redirection'],
        '/user',
        'user.page',
        ['404_if_empty' => TRUE],
      ],
      'same page as current page' => [
        ['field_redirect_link' => ['uri' => 'internal:/user']],
      ],
      'same page as current page, absolute' => [
        ['field_redirect_link' => ['uri' => $request->getSchemeAndHttpHost() . $request->getBasePath() . '/user']],
      ],
    ];
  }

  /**
   * Tests the builder for redirect state.
   */
  public function testFieldRedirectionResultBuilderSuccess() {
    $entity = $this->createTestEntity(['field_redirect_link' => ['uri' => 'http://example.com']]);
    $builder = \Drupal::service('field_redirection.result_builder');
    $request = Request::create('/user');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'user.page');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/user'));
    \Drupal::requestStack()->push($request);
    $this->assertEquals(
      FieldRedirectionResult::fromUrl(Url::fromUri('http://example.com')),
      $builder->buildResult($entity->get('field_redirect_link'), $request, $this->createUser())
    );
  }

  /**
   * Creates a test entity.
   *
   * @param array $values
   *   Optional values to create with.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function createTestEntity(array $values = []): EntityInterface {
    $entity = EntityTest::create($values + [
      'name' => $this->randomMachineName(),
      'type' => 'entity_test',
    ]);
    $entity->save();
    return $entity;
  }

}
