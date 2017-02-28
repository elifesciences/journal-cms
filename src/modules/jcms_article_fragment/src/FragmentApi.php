<?php

namespace Drupal\jcms_article_fragment;

use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Class FragmentApi.
 *
 * @package Drupal\jcms_article_fragment
 */
class FragmentApi {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * FragmentApi constructor.
   *
   * @param \GuzzleHttp\Client|NULL $client
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * Post the image fragment.
   *
   * @param int $imageFid
   * @param string $articleId
   *
   * @return \GuzzleHttp\Psr7\Response
   */
  public function postImageFragment(int $imageFid, string $articleId, string $alt = ''): Response {
    $endpoint = sprintf(Settings::get('jcms_article_fragment_images_endpoint'), $articleId);
    $payload = $this->getPayLoad($imageFid, $alt);
    $response = $this->client->post($endpoint, [
      'body' => $payload,
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
        'Content-Type' => 'application/json',
      ],
      'http_errors' => TRUE,
    ]);
    return $response;
  }

  /**
   * Get an image style URL from a file ID.
   *
   * @param $imageFid
   * @param $imageStyle
   *
   * @return string
   */
  public function getImageUri(int $imageFid, string $imageStyle): string {
    $file = File::load($imageFid);
    $path = $file->getFileUri();
    $url = ImageStyle::load($imageStyle)->buildUrl($path);
    return $url;
  }

  /**
   * Gets the JSON payload for the fragment.
   *
   * @param int $imageFid
   * @param string $alt
   *
   * @return string
   */
  public function getPayLoad(int $imageFid, string $alt = ''): string {
    $images = [
      'image' => [
        'banner' => [
          'alt' => $alt,
          'sizes' => [
            '2:1' => [
              900 => $this->getImageUri($imageFid, 'crop_2x1_900x450'),
              1800 => $this->getImageUri($imageFid, 'crop_2x1_1800x900'),
            ],
          ],
        ],
        'thumbnail' => [
          'alt' => $alt,
          'sizes' => [
            '16:9' => [
              250 => $this->getImageUri($imageFid, 'crop_16x9_250x141'),
              500 => $this->getImageUri($imageFid, 'crop_16x9_500x281'),
            ],
            '1:1' => [
              70 => $this->getImageUri($imageFid, 'crop_1x1_70x70'),
              140 => $this->getImageUri($imageFid, 'crop_1x1_140x140'),
            ],
          ],
        ],
      ],
    ];
    return json_encode($images);
  }

}
