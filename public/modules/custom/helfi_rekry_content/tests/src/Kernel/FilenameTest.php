<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests file name transliteration.
 *
 * @group helfi_rekry_content
 */
class FilenameTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['helfi_rekry_content'];

  /**
   * Tests the file name transliteration.
   *
   * @dataProvider filenameData
   */
  public function testFileName(?string $expectedFilename, ?string $filename) : void {
    $this->assertEquals($expectedFilename, _helfi_rekry_content_filename($filename));
  }

  /**
   * The data provider for file name tests.
   *
   * @return array
   *   The data.
   */
  public static function filenameData(): array {
    return [
      [
        NULL,
        NULL,
      ],
      [
        'laakari_sairaanhoitaja_kaytavalla.png',
        'https://helbit.fi/portal-api/recruitment/images/L%C3%A4%C3%A4k%C3%A4ri_sairaanhoitaja_k%C3%A4yt%C3%A4v%C3%A4ll%C3%A4.png?target=1&id=26398',
      ],
      [
        'arabian_peruskoulu_30082023_kuva_maija_astikainen-5565-edit_pieni.jpg',
        'https://helbit.fi/portal-api/recruitment/images/arabian_peruskoulu_30082023_kuva_maija_astikainen-5565-Edit_pieni.jpg?target=1&id=27012',
      ],
    ];
  }

}
