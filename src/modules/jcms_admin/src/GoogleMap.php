<?php

namespace Drupal\jcms_admin;

use Embed\Embed;
use Psr\Log\LoggerInterface;

/**
 * Class GoogleMap.
 */
final class GoogleMap implements GoogleMapInterface {
  private $logger;

  /**
   * GoogleMap constructor.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdFromUri(string $uri): string {
    if (preg_match('/mid=(?P<id>[^&]+)(|[&].*)$/', stripslashes($uri), $match)) {
      return $match['id'];
    }

    $this->logger->warning('Google map ID not found in uri.', ['uri' => $uri]);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(string $id): string {
    try {
      if ($info = Embed::create('https://www.youtube.com/watch?v=' . $id)) {
        if (isset($info->getProviders()['opengraph'])) {
          /* @var \Embed\Providers\OpenGraph $opengraph */
          $opengraph = $info->getProviders()['opengraph'];
          // Retrieve title of the google map.
          if ($title = $opengraph->getTitle()) {
            return $title;
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Google maps could not be reached.', ['id' => $id]);
    }
  }

}
