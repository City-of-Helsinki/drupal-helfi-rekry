<?php

declare(strict_types = 1);

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
    $node = $this->createNode([
      'type' => 'job_listing',
      'langcode' => 'sv',
      'title' => 'en jobb',
      'field_recruitment_id' => 'TESTI-1234-56-7890',
    ]);

    $path = $node->toUrl()->toString();
    $recruitmentId = array_reverse(explode('/', $path))[0];

    $this->drupalGetWithLanguage("/fi/avoimet-tyopaikat/avoimet-tyopaikat/$recruitmentId", 'fi');
    $url = $this->getSession()->getCurrentUrl();
    $this->assertTrue(str_ends_with($url, '/sv/lediga-jobb/lediga-jobb/testi-1234-56-7890'));
  }

}
