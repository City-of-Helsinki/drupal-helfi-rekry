<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Drupal\helfi_rekry_content\Service\HakuvahtiTracker;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Tests hakuvahti event subscriber.
 */
class HakuvahtiEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_rekry_content',
  ];

  /**
   * Tests alter event.
   */
  public function testHakuvahtiEventSubscriber(): void {
    $tracker = $this->prophesize(HakuvahtiTracker::class);

    $tracker->parseQuery(Argument::any(), Argument::any(), 'sv', Argument::any())
      ->shouldBeCalled()
      ->willReturn([['a', 'b', 'c'], ['d']]);

    $this->container->set(HakuvahtiTracker::class, $tracker->reveal());

    $alterEvent = new SubscriptionAlterEvent(new HakuvahtiRequest([
      'email' => 'valid@email.fi',
      'lang' => 'sv',
      'site_id' => 'rekry',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'this-is_the_base64_encoded_elasticsearch_query',
      'search_description' => 'This, is the query filters string, separated, by comma',
    ]));

    $dispatcher = $this->container->get(EventDispatcherInterface::class);
    $dispatcher->dispatch($alterEvent);

    // Alter event replaces the search description provided by the user.
    $this->assertEquals('a, b, c, d', $alterEvent->getHakuvahtiRequest()->searchDescription);
  }

}
