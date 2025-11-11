<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

/**
 * Hakuvahti request data object.
 *
 * Contains required fields & data for hakuvahti service request.
 */
final readonly class HakuvahtiRequest {

  private const MAX_SEARCH_DESCRIPTION_LENGTH = 999;

  /**
   * The email address.
   */
  public string $email;

  /**
   * Language id.
   */
  public string $lang;

  /**
   * The site id.
   */
  public string $siteId;

  /**
   * The request parameters from the request uli.
   */
  public string $query;

  /**
   * The elastic query as base64-encoded string.
   *
   * The query that is used to find out if there are new hits in elasticsearch.
   */
  public string $elasticQuery;

  /**
   * The search description.
   *
   * Search description is a string required by hakuvahti. According to
   * the initial spec, it's a comma-separated string of the selected search
   * filters, but it could be any other string as well.
   */
  public string $searchDescription;

  public function __construct(array $requestData) {
    $requiredFields = ['email', 'lang', 'site_id', 'query', 'elastic_query', 'search_description'];

    foreach ($requiredFields as $fieldName) {
      if (!isset($requestData[$fieldName])) {
        throw new \InvalidArgumentException("Request is missing field: $fieldName");
      }
    }

    if (!filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException("Email must be a valid email address");
    }

    if (strlen($requestData['search_description']) > self::MAX_SEARCH_DESCRIPTION_LENGTH) {
      throw new \InvalidArgumentException("Search description is too long.");
    }

    $this->email = $requestData['email'];
    $this->lang = $requestData['lang'];
    $this->siteId = $requestData['site_id'];
    $this->query = $requestData['query'];
    $this->elasticQuery = $requestData['elastic_query'];
    $this->searchDescription = $requestData['search_description'];
  }

  /**
   * Return the data to be sent for hakuvahti services subscription endpoint.
   *
   * @return array
   *   The data for hakuvahti subscription request.
   */
  public function getServiceRequestData(): array {
    return [
      'email' => $this->email,
      'lang' => $this->lang,
      'site_id' => $this->siteId,
      'query' => $this->query,
      'elastic_query' => $this->elasticQuery,
      'search_description' => $this->searchDescription,
    ];
  }

}
