<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unsubscribes from a subscription.
 */
final class HelfiHakuvahtiUnsubscribeController extends ControllerBase {

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
   * Constructor for the class.
   *
   * @param ClientInterface $http_client The HTTP client.
   * @param RequestStack $request_stack The request stack.
   * @param Token $token_service The token service.
   * @param AccountInterface $user The current user.
   */
  public function __construct(ClientInterface $http_client, RequestStack $request_stack, Token $token_service, AccountInterface $user) {
    $this->httpClient = $http_client;
    $this->requestStack = $request_stack;
    $this->tokenService = $token_service;
    $this->user = $user;
  }

  /**
   * Returns the form ID for unsubscribing from a subscription.
   *
   * @return string
   */
  public function getFormId() {
    return 'hakuvahti_unsubscribe_form';
  }

  /**
   * Builds the response for unsubscribing from a subscription.
   *
   * @return array
   */
  public function __invoke(): array {
    $build = [];

    $request = $this->requestStack->getCurrentRequest();
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    if ($this->isFormSubmitted()) {
      if ($this->sendUnsubscribeRequest($hash, $subscription)) {
        $build['confirmation'] = [
          '#title' => $this->t('Saved search deleted'),
        ];

        $build['confirmation']['paragraph'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('The saved search has been deleted'),
          '#attributes' => [
            'class' => ['page-title'],
          ],
        ];
        $build['confirmation']['paragraph2'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('You can save more searches at any time.'),
          '#attributes' => [
            'class' => ['page-title'],
          ],
        ];
        $build['confirmation']['link'] = [
          '#type' => 'link',
          '#tag' => 'a',
          '#title' => $this->t('Return to open jobs front page'),
          '#url' => '/',
        ];
      }
      else {
        $build['form']['paragraph'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Deleting saved search failed. Please try again.'),
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
        '#value' => $this->t('Please confirm that you wish to delete the saved search. If you have other searches saved on the City website, this link will not delete them.'),
      ];

      $build['form']['button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete saved search'),
        '#attributes' => [
          'class' => ['my-button'],
        ],
      ];
    }

    return $build;
  }

  /**
   * Returns the URL for the current form action.
   *
   * @return string The URL for the current form action.
   */
  protected function getFormActionUrl(): string {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

  /**
   * Checks if the form is submitted via the POST method.
   *
   * @return bool Whether the form is submitted via the POST method.
   */
  protected function isFormSubmitted(): bool {
    $request = $this->requestStack->getCurrentRequest();
    return $request->isMethod('POST');
  }

  /**
   * Sends an unsubscribe request to the server.
   *
   * @param string $hash The hash of the subscription.
   * @param string $subscription The subscription ID.
   * @throws RequestException Description of the request exception.
   * @return bool Returns TRUE if the request is successful, FALSE otherwise.
   */
  protected function sendUnsubscribeRequest(string $hash, string $subscription): bool {
    $expectedToken = \Drupal::service('csrf_token')->get('session');

    $httpClient = new Client();
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'token' => $expectedToken,
      ],
    ];

    $hakuvahtiServer = getenv('HAKUVAHTI_URL') ? getenv('HAKUVAHTI_URL') : 'http://helfi-rekry.docker.so:3000';

    try {
      $target_url = $hakuvahtiServer . '/subscription/delete/' . $subscription . '/' . $hash;
      $response = $httpClient->delete($target_url, $options);

      return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
    catch (RequestException $exception) {
      return FALSE;
    }
  }

}
