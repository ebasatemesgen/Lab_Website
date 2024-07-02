<?php

namespace Drupal\Tests\datetime_range\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests datetime_range update paths.
 *
 * @group datetime
 */
class DateRangeUpdateTest extends UpdatePathTestBase {

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installedStorageSchema = $this->container->get('keyvalue')->get('entity.storage_schema.sql');
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../datetime/tests/fixtures/update/dump.php.gz',
    ];
  }

  /**
   * Tests the end_value allows NULL values.
   *
   * @see datetime_range_update_8600()
   */
  public function testEndValueNull() {
    // Check all storage types before update.
    $this->assertEndValueNotNull('node', 'field_daterange_allday', TRUE);
    $this->assertEndValueNotNull('node', 'field_daterange_date', TRUE);
    $this->assertEndValueNotNull('node', 'field_daterange_datetime', TRUE);

    // Check a not revisinable field before update.
    $this->assertEndValueNotNull('taxonomy_term', 'field_daterange_not_revisionable', TRUE);

    $this->runUpdates();

    // Check all the storage types now allow null.
    $this->assertEndValueNotNull('node', 'field_daterange_allday', FALSE);
    $this->assertEndValueNotNull('node', 'field_daterange_date', FALSE);
    $this->assertEndValueNotNull('node', 'field_daterange_datetime', FALSE);

    // Check the not revisinable field now allow null.
    $this->assertEndValueNotNull('taxonomy_term', 'field_daterange_not_revisionable', FALSE);
  }

  /**
   * Asserts that a config depends on 'entity_reference' or not
   *
   * @param string $entity_type
   *   The entity type, the field belongs to.
   * @param string $field_name
   *   Name of the field
   * @param bool $expected
   *   The value expected of not null.
   */
  protected function assertEndValueNotNull($entity_type, $field_name, $expected) {
    $schema_key = $entity_type . '.field_schema_data.' . $field_name;
    foreach ($this->installedStorageSchema->get($schema_key) as $table) {
      $this->assertEquals($expected, $table['fields'][$field_name . '_end_value']['not null']);
    }
  }

}
