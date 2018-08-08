<?php

namespace Drupal\jcms_admin;

use Embed\Embed;
use Psr\Log\LoggerInterface;

/**
 * Class YouTube.
 */
final class YouTube implements YouTubeInterface {
  private $logger;

  /**
   * YouTube constructor.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdFromUri(string $uri) : string {
    if (preg_match('/^(|.*[^a-zA-Z0-9_-])(?P<id>[a-zA-Z0-9_-]{11})(|[^a-zA-Z0-9_-].*)$/', stripslashes($uri), $match)) {
      return $match['id'];
    }

    $this->logger->warning('YouTube ID not forund in uri.', ['uri' => $uri]);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensions(string $id) : array {
    try {
      if ($info = Embed::create('https://www.youtube.com/watch?v=' . $id)) {
        if (isset($info->getProviders()['opengraph'])) {
          /* @var \Embed\Providers\OpenGraph $opengraph */
          $opengraph = $info->getProviders()['opengraph'];
          // Store width and height of video.
          if ($opengraph->getWidth() && $opengraph->getHeight()) {
            return [
              'width' => (int) $opengraph->getWidth(),
              'height' => (int) $opengraph->getHeight(),
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('YoutTube could not be reached.', ['id' => $id]);
    }

    return [
      'width' => 16,
      'height' => 9,
    ];
  }

}
