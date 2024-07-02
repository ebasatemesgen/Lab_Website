<?php

namespace Drupal\Tests\entity_pager\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\entity_pager\EntityPagerAnalyzer;
use Drupal\entity_pager\EntityPagerInterface;
use Drupal\entity_pager\Event\EntityPagerAnalyzeEvent;
use Drupal\entity_pager\Event\EntityPagerEvents;
use Drupal\Tests\UnitTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\entity_pager\EntityPagerAnalyzer
 * @group entity_pager
 */
class EntityPagerAnalyzerTest extends UnitTestCase {

  /**
   * Log messages to use in tests.
   *
   * @var string[]
   */
  protected $logs;

  /**
   * Entity pager stub.
   *
   * @var \Drupal\entity_pager\EntityPagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityPager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityPager = $this->createMock(EntityPagerInterface::class);

    for ($i = 0; $i < random_int(3, 6); $i++) {
      $this->logs[] = $this->randomMachineName();
    }
  }

  /**
   * @covers ::__construct
   * @covers ::analyze
   */
  public function testAnalyze() {
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher
      ->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->callback([$this, 'mockSubscriberLog']),
        EntityPagerEvents::ENTITY_PAGER_ANALYZE
      );

    // @todo Refactor \Drupal\entity_pager\EntityPagerAnalyzer to use dependency
    //   injection for its logging.
    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger
      ->expects($this->exactly(count($this->logs)))
      ->method('notice')
      ->withConsecutive(...array_map(function ($a) {
        return [$a];
      }, $this->logs));
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);
    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);

    $analyzer = new EntityPagerAnalyzer($event_dispatcher);
    $analyzer->analyze($this->entityPager);
  }

  /**
   * Event subscription mock function for ::testAnalyze().
   *
   * @param mixed $event
   *   The analyze event.
   *
   * @return bool
   *   Returns TRUE if given an
   *   \Drupal\entity_pager\Event\EntityPagerAnalyzeEvent, FALSE otherwise.
   */
  public function mockSubscriberLog($event) {
    if ($event instanceof EntityPagerAnalyzeEvent) {
      $event->log($this->logs);
      $this->assertSame($this->entityPager, $event->getEntityPager());
      return TRUE;
    }

    return FALSE;
  }

}
