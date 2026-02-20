<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for helfi_rekry_content kernel tests.
 */
abstract class RekryKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_rekry_content',
    'helfi_hakuvahti',
  ];

}
