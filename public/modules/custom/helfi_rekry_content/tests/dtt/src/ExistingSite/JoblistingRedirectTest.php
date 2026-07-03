<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Tests\dtt\src\ExistingSite;

use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;

/**
 * Test job listing redirect.
 *
 * @group dtt
 */
class JoblistingRedirectTest extends ExistingSiteTestBase {

  /**
   * Test job listing 404 redirect.
   */
  public function test404Redirect(): void {
    $recruitmentId = 'TESTI-' . random_int(1000, 9999) . '-' . random_int(10, 99) . '-' . random_int(1000, 9999);

    $node = $this->createNode([
      'type' => 'job_listing',
      'langcode' => 'sv',
      'title' => 'en jobb',
      'field_recruitment_id' => $recruitmentId,
    ]);

    $expected = '/sv/lediga-jobb/lediga-jobb/' . strtolower($recruitmentId);
    $this->assertStringEndsWith($expected, $node->toUrl()->toString());

    $this->drupalGetWithLanguage('/fi/avoimet-tyopaikat/avoimet-tyopaikat/' . strtolower($recruitmentId), 'fi');
    $this->assertStringEndsWith($expected, $this->getSession()->getCurrentUrl());
  }

}
