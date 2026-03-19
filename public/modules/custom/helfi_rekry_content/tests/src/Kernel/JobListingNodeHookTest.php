<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\helfi_rekry_content\Hook\JobListingNodeHook;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the JobListingNodeHook().
 */
#[Group('helfi_rekry_content')]
class JobListingNodeHookTest extends RekryKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * Tests that a rekry_admin user sees a warning but fields are not disabled.
   */
  public function testWarningShownForRekryAdmin(): void {
    $form = [];
    $this->createHook(isRekryAdmin: TRUE)
      ->jobListingNodeFormAlter($form, $this->createFormState(FALSE));

    $messages = $this->container->get(MessengerInterface::class)
      ->messagesByType(MessengerInterface::TYPE_WARNING);
    $this->assertCount(1, $messages);
    $this->assertEmpty($form);
  }

  /**
   * Tests that fields are disabled for a user without rekry_admin role.
   */
  public function testFieldsDisabledForEditor(): void {
    $form = [];
    $this->createHook(isRekryAdmin: FALSE)
      ->jobListingNodeFormAlter($form, $this->createFormState(FALSE));

    $messages = $this->container->get(MessengerInterface::class)
      ->messagesByType(MessengerInterface::TYPE_WARNING);
    $this->assertCount(0, $messages);
    $this->assertTrue($form['title']['#disabled']);
    $this->assertTrue($form['field_image']['#disabled']);
  }

  /**
   * Tests that nothing is done when the user has entity edit access.
   */
  public function testNothingDoneWhenUserHasEditAccess(): void {
    $form = [];
    $this->createHook(isRekryAdmin: FALSE)
      ->jobListingNodeFormAlter($form, $this->createFormState(TRUE));

    $messages = $this->container->get(MessengerInterface::class)->messagesByType(MessengerInterface::TYPE_WARNING);
    $this->assertCount(0, $messages);
    $this->assertEmpty($form);
  }

  /**
   * Creates a hook instance with mocked dependencies.
   */
  private function createHook(bool $isRekryAdmin): JobListingNodeHook {
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $currentUser->method('getRoles')->willReturn($isRekryAdmin ? ['rekry_admin'] : []);

    return new JobListingNodeHook(
      $this->container->get(MessengerInterface::class),
      $currentUser,
    );
  }

  /**
   * Creates a form state with a mocked entity form object.
   */
  private function createFormState(bool $editAccess): FormState {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('access')->willReturn($editAccess);

    $formObject = $this->createMock(EntityFormInterface::class);
    $formObject->method('getEntity')->willReturn($entity);

    $form_state = new FormState();
    $form_state->setFormObject($formObject);

    return $form_state;
  }

}
