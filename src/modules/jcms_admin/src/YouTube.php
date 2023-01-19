<?php

namespace Drupal\jcms_admin;

use Psr\Log\LoggerInterface;

/**
 * Class YouTube Embed.
 */
final class YouTube implements YouTubeInterface {

  /**
   * The Embed.
   *
   * @var \Drupal\jcms_admin\Embed
   */
  private $embed;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * YouTube constructor.
   */
  public function __construct(Embed $embed, LoggerInterface $logger) {
    $this->embed = $embed;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdFromUri(string $uri) : string {
    if (preg_match('/^(|.*[^a-zA-Z0-9_-])(?P<id>[a-zA-Z0-9_-]{11})(|[^a-zA-Z0-9_-].*)$/', stripslashes($uri), $match)) {
      return $match['id'];
    }

    $this->logger->warning('YouTube ID not found in uri.', ['uri' => $uri]);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensions(string $id) : array {
    try {
      if ($info = $this->embed->create('https://www.youtube.com/watch?v=' . $id)) {
        $providers = $info->getProviders();
        if (isset($providers['opengraph'])) {
          /** @var \Embed\Providers\OpenGraph $opengraph */
          $opengraph = $providers['opengraph'];
          $width = $opengraph->getWidth();
          $height = $opengraph->getHeight();
          // Return width and height of video.
          if ($width && $height) {
            return [
              'width' => (int) $width,
              'height' => (int) $height,
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('YouTube could not be reached.', ['id' => $id]);
    }

    return [
      'width' => 16,
      'height' => 9,
    ];
  }

}
