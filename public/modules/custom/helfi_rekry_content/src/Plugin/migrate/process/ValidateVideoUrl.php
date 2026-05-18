<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\migrate\process;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sanitises and validates an oEmbed video URL.
 *
 * Trims whitespace and rewrites known YouTube short forms to the canonical
 * watch?v=[id] form before validating the URL against the allowed oEmbed
 * providers and fetching the resource.
 *
 * Returns the canonical URL when validation succeeds. Returns NULL when the
 * URL is empty, the oEmbed provider is not in the allow-list, or the oEmbed
 * resource cannot be fetched.
 *
 * @code
 * field_media_oembed_video:
 *   - plugin: validate_video_url
 *     source: video
 * @endcode
 */
#[MigrateProcess(
  id: 'validate_video_url',
)]
final class ValidateVideoUrl extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  private const array ALLOWED_PROVIDERS = ['YouTube', 'Icareus Suite'];

  /**
   * The constructor.
   *
   * @phpstan-param array<mixed> $configuration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    mixed $plugin_definition,
    protected UrlResolverInterface $urlResolver,
    protected ResourceFetcherInterface $resourceFetcher,
    protected LoggerChannelInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(UrlResolverInterface::class),
      $container->get(ResourceFetcherInterface::class),
      $container->get('logger.factory')->get('helfi_rekry_content'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : ?string {
    $value = self::sanitizeUrl($value);

    if ($value === '') {
      return NULL;
    }

    try {
      $provider = $this->urlResolver->getProviderByUrl($value);

      if (!in_array($provider->getName(), self::ALLOWED_PROVIDERS, TRUE)) {
        $this->logger->notice('Video embed url "@url" rejected: provider "@provider" is not allowed.', [
          '@url' => $value,
          '@provider' => $provider->getName(),
        ]);
        return NULL;
      }
    }
    catch (ResourceException | ProviderException | \InvalidArgumentException $e) {
      $this->logger->notice('Video embed url "@url" failed validation: @message', [
        '@url' => $value,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }

    // Ticket #UHF-9069 prevent migrating bad oembed links.
    try {
      $this->resourceFetcher->fetchResource($this->urlResolver->getResourceUrl($value));
    }
    catch (ResourceException | ProviderException $e) {
      $this->logger->error('Bad video url rejected by oembed-validation: @url', [
        '@url' => $value,
      ]);
      return NULL;
    }

    return $value;
  }

  /**
   * Trim and canonicalise YouTube short-form URLs to watch?v=[id] form.
   */
  private static function sanitizeUrl(mixed $url): string {
    if (!is_string($url)) {
      return '';
    }

    $url = trim($url);
    if ($url === '') {
      return '';
    }

    // Some valid YouTube links are not recognized by drupal/oembed_providers
    // module, which triggers additional network requests that attempt to sniff
    // oembed links directly from YouTube. However, YouTube does not like
    // automated traffic from datacenters, so these requests often fail in
    // production.
    //
    // This regex tries to pick video id from following patters and
    // formats the links to the expected format.
    //
    // Features:
    // - https:// or www. missing.
    // - youtube.com/v/[id].
    // - youtu.be/[id] short links.
    // - youtube.com/embed/[id].
    if (preg_match("/youtu(?:.*\/v\/|.*v=|\.be\/|.*\/embed\/)([A-Za-z0-9_\-]{11})/", $url, $matches)) {
      $url = sprintf("https://www.youtube.com/watch?v=%s", $matches[1]);
    }

    return $url;
  }

}
