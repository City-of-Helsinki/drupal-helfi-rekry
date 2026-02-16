<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Drupal\helfi_rekry_content\Service\HakuvahtiTracker;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Tests hakuvahti event subscriber.
 */
#[RunTestsInSeparateProcesses]
class HakuvahtiEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_rekry_content',
  ];

  /**
   * Data provider for search description tests.
   */
  public static function searchDescriptionDataProvider(): array {
    return [
      'filters are joined' => [
        [['a', 'b', 'c'], ['d']],
        'a, b, c, d',
      ],
      'vapaa-sana is included' => [
        ['vapaa-sana' => ['search'], 'other' => ['filter1', 'filter2']],
        'search, filter1, filter2',
      ],
      'empty filters' => [
        [],
        '',
      ],
      'user-provided description is replaced' => [
        [['safe-filter']],
        'safe-filter',
      ],
      'vapaa-sana is truncated' => [
        ['vapaa-sana' => ['some keyword']],
        'some keyw…',
      ],
    ];
  }

  /**
   * Tests that searchDescription is computed correctly from filters.
   */
  #[DataProvider('searchDescriptionDataProvider')]
  public function testSearchDescription(array $filters, string $expected): void {
    $tracker = $this->prophesize(HakuvahtiTracker::class);
    $tracker->parseQuery(Argument::any(), Argument::any(), Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn($filters);
    $this->container->set(HakuvahtiTracker::class, $tracker->reveal());

    $event = new SubscriptionAlterEvent(new HakuvahtiRequest([
      'email' => 'valid@email.fi',
      'lang' => 'sv',
      'siteId' => 'rekry',
      'query' => '?query=123&parameters=4567',
      'elasticQuery' => 'this-is_the_base64_encoded_elasticsearch_query',
      'searchDescription' => 'Original description',
    ]));

    $dispatcher = $this->container->get(EventDispatcherInterface::class);
    $dispatcher->dispatch($event);

    $this->assertEquals($expected, $event->getHakuvahtiRequest()->searchDescription);
  }

}
