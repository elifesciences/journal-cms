<?php

namespace Drupal\jcms_admin;

use PHPHtmlParser\Dom;
use Psr\Log\LoggerInterface;
use Drupal\media\OEmbed\UrlResolver;
use Drupal\media\OEmbed\ResourceFetcher;

/**
 * Class YouTube.
 */
final class Tweet implements TweetInterface {

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The Oembed Url Resolver.
   *
   * @var \Drupal\media\OEmbed\UrlResolver
   */
  private $urlResolver;

  /**
   * The Oembed Resource Fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcher
   */
  private $resourceFetcher;

  /**
   * Tweet constructor.
   */
  public function __construct(LoggerInterface $logger, UrlResolver $url_resolver, ResourceFetcher $resource_fetcher) {
    $this->logger = $logger;
    $this->urlResolver = $url_resolver;
    $this->resourceFetcher = $resource_fetcher;
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
      $url = 'https://twitter.com/og/status/' . $id;
      $resourceUrl = $this->urlResolver->getResourceUrl($url);
      if ($resourceUrl) {
        $oembed = $this->resourceFetcher->fetchResource($resourceUrl);
        if ($oembed) {
          $oembed_dom = new Dom();
          $oembed_dom->setOptions([
            'preserveLineBreaks' => TRUE,
          ]);
          $oembed_dom->load($oembed->getHtml());
          $blockquote = $oembed_dom->find('blockquote');
          $text = $blockquote->firstChild()->innerHtml();
          $datestr = $blockquote->lastChild()->text();
          $date = strtotime($datestr);
          if (empty($date)) {
            $date = time();
          }
          $account_label = $oembed->getAuthorName();
          if (preg_match('/\(\@([^\)]+)\)/', $blockquote->text(), $matches)) {
            $account_id = $matches[1];
          }
          else {
            $account_id = $account_label;
          }
          // Retrieve details of the tweet.
          return array_filter([
            'date' => $date,
            'accountId' => $account_id,
            'accountLabel' => $account_label,
            'text' => $text,
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Twitter could not be reached.', [
        'id' => $id,
        'error' => $e->getMessage(),
      ]);
    }

    return [];
  }

}
