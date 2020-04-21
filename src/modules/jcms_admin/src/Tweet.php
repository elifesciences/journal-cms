<?php

namespace Drupal\jcms_admin;

use PHPHtmlParser\Dom;
use Psr\Log\LoggerInterface;

/**
 * Class YouTube.
 */
final class Tweet implements TweetInterface {
  private $embed;
  private $logger;

  /**
   * Tweet constructor.
   */
  public function __construct(Embed $embed, LoggerInterface $logger) {
    $this->embed = $embed;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdFromUri(string $uri): string {
    if (preg_match('/\/status\/(?P<id>[0-9]+)(|[^0-9].*)$/', stripslashes($uri), $match)) {
      return $match['id'];
    }

    $this->logger->warning('Tweet status ID not found in uri.', ['uri' => $uri]);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(string $id): array {
    try {
      if ($info = $this->embed->create('https://twitter.com/eLife/status/' . $id)) {
        $providers = $info->getProviders();
        if (isset($providers['opengraph'])) {
          /* @var \Embed\Providers\OpenGraph $opengraph */
          $opengraph = $providers['opengraph'];
          $oembed = $providers['oembed'];
          $oembed_dom = new Dom();
          $oembed_dom->setOptions([
            'preserveLineBreaks' => TRUE,
          ]);
          $oembed_dom->load($oembed->getCode());
          $blockquote = $oembed_dom->find('blockquote');
          $datestr = $blockquote->lastChild()->text();
          $date = strtotime($datestr);
          if (empty($date)) {
            $date = time();
          }
          if (preg_match('/\(\@([^\)]+)\)/', $blockquote->text(), $matches)) {
            $account_id = $matches[1];
          }
          else {
            $account_id = $opengraph->getTitle();
          }
          // Retrieve details of the tweet.
          return array_filter([
            'date' => $date,
            'accountId' => $account_id,
            'accountLabel' => $opengraph->getTitle(),
            'text' => $opengraph->getDescription(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Twitter could not be reached.', ['id' => $id]);
    }
  }

}
