<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

/**
 * Simple response class to hold the relevant response data.
 */
class Response {

  /**
   * The constructor.
   *
   * @param array $urls
   *   Url which were indexed.
   * @param array $errors
   *   Errors per url.
   * @param bool $debug
   *   The request was not sent.
   */
  public function __construct(
    private array $urls,
    private array $errors = [],
    private bool $debug = FALSE,
  ) {
  }

  /**
   * Get request urls.
   *
   * @return array
   *   the urls.
   */
  public function getUrls(): array {
    return $this->urls;
  }

  /**
   * Get the errors.
   *
   * @return array
   *   Errors for each url.
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * The request was not actually sent.
   *
   * Either the api key is not set or
   * in settings config, enabled in false.
   *
   * @return bool
   *   This is a debug run.
   */
  public function isDebug(): bool {
    return $this->debug;
  }

}
