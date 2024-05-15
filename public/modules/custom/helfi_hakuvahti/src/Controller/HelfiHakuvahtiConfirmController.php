<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Confirms a subscription.
 */
final class HelfiHakuvahtiConfirmController extends ControllerBase {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The CSRF token service.
   *
   * @var \Drupal\Core\CsrfToken\CsrfTokenManagerInterface
   */
  protected $csrfTokenService;

  /**
   * Constructor for HelfiHakuvahtiConfirmController.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account.
   */
  public function __construct(
    ClientInterface $http_client,
    ContainerInterface $container,
    RequestStack $request_stack,
    Token $token_service,
    AccountInterface $user,
  ) {
    $this->httpClient = $http_client;
    $this->csrfTokenService = $container->get('csrf_token');
    $this->requestStack = $request_stack;
    $this->tokenService = $token_service;
    $this->user = $user;
  }

  /**
   * Returns the form ID for the confirmation form.
   *
   * @return string
   *   The Form name
   */
  public function getFormId() {
    return 'hakuvahti_confirm_form';
  }

  /**
   * Executes the confirmation process for a saved search.
   *
   * @return array
   *   The build array containing the confirmation form or the
   *   saved search form.
   */
  public function __invoke(): array {
    $build = [];

    $request = $this->requestStack->getCurrentRequest();
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    if ($this->isFormSubmitted()) {
      if ($this->sendConfirmationRequest($hash, $subscription)) {
        $build['confirmation'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Saved search confirmed.'),
          '#attributes' => [
            'class' => ['page-title'],
          ],
        ];
      }
      else {
        $build['confirmation'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Confirming saved search failed. Please try again.'),
          '#attributes' => [
            'class' => ['page-title'],
          ],
        ];
      }
    }
    else {
      $build['form'] = [
        '#type' => 'form',
        '#attributes' => [
          'class' => ['page-title'],
        ],
        '#id' => $this->getFormId(),
        '#form_id' => $this->getFormId(),
        '#action' => $this->getFormActionUrl(),
        '#method' => 'POST',
      ];

      $build['form']['paragraph'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Please confirm the saved search to receive notifications. Click on the button below.'),
      ];

      $build['form']['button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm saved search'),
        '#attributes' => [
          'class' => ['my-button'],
        ],
      ];
    }

    return $build;
  }

  /**
   * Retrieves the form action URL from the current request.
   *
   * @return string
   *   The URL of the current request.
   */
  protected function getFormActionUrl(): string {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

  /**
   * Checks if the form is submitted via POST method.
   *
   * @return bool
   *   Whether the form is submitted via POST method.
   */
  protected function isFormSubmitted(): bool {
    $request = $this->requestStack->getCurrentRequest();
    return $request->isMethod('POST');
  }

  /**
   * Sends a confirmation request to the Hakuvahti server.
   *
   * @param string $subscriptionHash
   *   The subscription hash.
   * @param string $subscriptionId
   *   The subscription ID.
   *
   * @return bool
   *   Returns TRUE if the confirmation request is successful, FALSE otherwise.
   */
  protected function sendConfirmationRequest(string $subscriptionHash, string $subscriptionId): bool {
    $expectedToken = $this->csrfTokenService->get('session');
    $httpClient = new Client([
      'headers' => [
        'Content-Type' => 'application/json',
        'token' => $expectedToken,
      ],
    ]);

    $hakuvahtiServer = getenv('HAKUVAHTI_URL') ?: 'http://helfi-rekry.docker.so:3000';
    $targetUrl = $hakuvahtiServer . '/subscription/confirm/' . $subscriptionId . '/' . $subscriptionHash;

    try {
      $response = $httpClient->get($targetUrl);
      return $response->getBody()->getContents() !== '';
    }
    catch (RequestException $exception) {
      return FALSE;
    }
  }

}
