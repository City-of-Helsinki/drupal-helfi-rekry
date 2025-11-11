<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Unit;

use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Drupal\Tests\UnitTestCase;

/**
 * Tests rekry specific hakuvahti features.
 *
 * @group helfi_rekry_content
 */
class HakuvahtiRequestClassTest extends UnitTestCase {

  /**
   * Test that an exception is thrown if request has missing parameters.
   */
  public function testMissingRequestParameter(): void {
    $this->expectException(\InvalidArgumentException::class);
    new HakuvahtiRequest([]);
  }

  /**
   * Test the request class.
   */
  public function testRequestClass(): void {
    $requiredFields = $this->getRequiredData();

    try {
      $requiredFields['email'] = 'invalid@email';
      new HakuvahtiRequest($requiredFields);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertIsObject($e, 'Validated email address format');
    }

    $requiredFields = $this->getRequiredData();
    $request = new HakuvahtiRequest($requiredFields);

    $serviceRequestData = $request->getServiceRequestData();
    $this->assertEquals($serviceRequestData, $this->getRequiredData());
    $this->assertEquals($serviceRequestData['elastic_query'], $request->elasticQuery);
    $this->assertEquals($serviceRequestData['search_description'], $request->searchDescription);
    $this->assertEquals($serviceRequestData['query'], $request->query);
  }

  /**
   * Get the initial request data.
   *
   * @return array
   *   The hakuvahti initial request data.
   */
  private function getRequiredData(): array {
    return [
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'site_id' => 'rekry',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'this-is_the_base64_encoded_elasticsearch_query',
      'search_description' => 'This, is the query filters string, separated, by comma',
    ];
  }

}
