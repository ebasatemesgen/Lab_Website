<?php

namespace Drupal\Tests\entity_pager\Unit;

use Drupal\entity_pager\EntityPagerInterface;
use Drupal\entity_pager\Event\EntityPagerAnalyzeEvent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_pager\Event\EntityPagerAnalyzeEvent
 * @group entity_pager
 */
class EntityPagerAnalyzeEventTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::getEntityPager
   */
  public function testGetEntityPager() {
    $entity_pager = $this->createMock(EntityPagerInterface::class);
    $event = new EntityPagerAnalyzeEvent($entity_pager);
    $this->assertSame($entity_pager, $event->getEntityPager());
  }

  /**
   * @covers ::__construct
   * @covers ::getEntityPager
   * @covers ::setEntityPager
   */
  public function testSetEntityPager() {
    $event = new EntityPagerAnalyzeEvent($this->createMock(EntityPagerInterface::class));

    $entity_pager = $this->createMock(EntityPagerInterface::class);
    $event->setEntityPager($entity_pager);
    $this->assertSame($entity_pager, $event->getEntityPager());
  }

  /**
   * @covers ::__construct
   * @covers ::getLogs
   * @covers ::setLogs
   * @covers ::log
   */
  public function testGetSetLogs() {
    $event = new EntityPagerAnalyzeEvent($this->createMock(EntityPagerInterface::class));

    $logs1 = [$this->randomMachineName(), $this->randomMachineName()];
    $event->setLogs($logs1);
    $this->assertSame($logs1, $event->getLogs());

    $log2 = $this->randomMachineName();
    $event->log($log2);
    $this->assertEquals(array_merge($logs1, [$log2]), $event->getLogs(), 'log() accepts string argument.');

    $log3 = [$this->randomMachineName(), $this->randomMachineName()];
    $event->log($log3);
    $this->assertEquals(array_merge($logs1, [$log2], $log3), $event->getLogs(), 'log() accepts array argument.');
  }

}
