<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Unit\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests JobListing entity methods.
 *
 * Tests covered:
 * - getJobDescription: Override vs default field behavior
 * - getOrganizationName: Organization field without override
 * - getCityDescriptions: Configuration-based content retrieval.
 *
 * @group helfi_rekry_content
 * @coversDefaultClass \Drupal\helfi_rekry_content\Entity\JobListing
 */
class JobListingTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The JobListing entity under test.
   *
   * @var \Drupal\helfi_rekry_content\Entity\JobListing&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $jobListing;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a partial mock of JobListing to test specific methods.
    $this->jobListing = $this->getMockBuilder(JobListing::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
  }

  /**
   * Tests getJobDescription with override value.
   *
   * @covers ::getJobDescription
   */
  public function testGetJobDescriptionWithOverride(): void {
    $override_field = $this->createMockField('Override job description');

    $this->jobListing->expects($this->once())
      ->method('get')
      ->with('field_job_description_override')
      ->willReturn($override_field);

    $result = $this->jobListing->getJobDescription();
    $this->assertEquals('Override job description', $result);
  }

  /**
   * Tests getJobDescription without override value.
   *
   * @covers ::getJobDescription
   */
  public function testGetJobDescriptionWithoutOverride(): void {
    $override_field = $this->createMockField(NULL);
    $default_field = $this->createMockField('Default job description');

    $this->jobListing->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['field_job_description_override', $override_field],
        ['job_description', $default_field],
      ]);

    $result = $this->jobListing->getJobDescription();
    $this->assertEquals('Default job description', $result);
  }

  /**
   * Tests getJobDescription with empty fields.
   *
   * @covers ::getJobDescription
   */
  public function testGetJobDescriptionWithEmptyFields(): void {
    $override_field = $this->createMockField(NULL);
    $default_field = $this->createMockField(NULL);

    $this->jobListing->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['field_job_description_override', $override_field],
        ['job_description', $default_field],
      ]);

    $result = $this->jobListing->getJobDescription();
    $this->assertEquals('', $result);
  }

  /**
   * Tests getOrganizationName without override.
   *
   * @covers ::getOrganizationName
   */
  public function testGetOrganizationNameWithoutOverride(): void {
    $override_field = $this->createMockFieldWithFirst(FALSE);
    $name_field = $this->createMockField('Test Organization');

    $this->jobListing->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['field_organization_override', $override_field],
        ['field_organization_name', $name_field],
      ]);

    $result = $this->jobListing->getOrganizationName();
    $this->assertEquals('Test Organization', $result);
  }

  /**
   * Tests getCityDescriptions.
   *
   * @covers ::getCityDescriptions
   */
  public function testGetCityDescriptions(): void {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('city_description_title')->willReturn('About Helsinki');
    $config->get('city_description_text')->willReturn('Helsinki is the capital of Finland');

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('helfi_rekry_content.job_listings')->willReturn($config->reveal());

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('config.factory')->willReturn($config_factory->reveal());
    \Drupal::setContainer($container->reveal());

    $result = $this->jobListing->getCityDescriptions();

    $expected = [
      '#city_description_title' => 'About Helsinki',
      '#city_description_text' => 'Helsinki is the capital of Finland',
    ];

    $this->assertEquals($expected, $result);
  }

  /**
   * Creates a mock field with a value.
   *
   * @param mixed $value
   *   The field value.
   *
   * @return object
   *   The mock field.
   */
  private function createMockField($value) {
    $field = new \stdClass();
    $field->value = $value;
    return $field;
  }

  /**
   * Creates a mock field with a first() method.
   *
   * @param mixed $first_return
   *   What first() should return.
   *
   * @return object
   *   The mock field.
   */
  private function createMockFieldWithFirst($first_return) {
    $field = $this->prophesize(FieldItemListInterface::class);
    $field->first()->willReturn($first_return);
    return $field->reveal();
  }

}
