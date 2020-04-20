<?php

namespace Drupal\jcms_admin;

use Embed\Embed;
use PHPHtmlParser\Dom;
use Psr\Log\LoggerInterface;

/**
 * Class YouTube.
 */
final class Tweet implements TweetInterface {
  private $logger;

  /**
   * Tweet constructor.
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

    $this->logger->warning('Tweet status ID not found in uri.', ['uri' => $uri]);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(string $id) : array {
    try {
      if ($info = Embed::create('https://twitter.com/eLife/status/' . $id)) {
        if (isset($info->getProviders()['opengraph'])) {
          /* @var \Embed\Providers\OpenGraph $opengraph */
          $opengraph = $info->getProviders()['opengraph'];
          $oembed = $info->getProviders()['oembed'];
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
          // Store details of tweet.
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
