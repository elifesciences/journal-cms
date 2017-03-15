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
   * @param string $articleId
   * @param int    $thumbFid
   * @param string $thumbAlt
   * @param int    $bannerFid
   * @param string $bannerAlt
   * @param int    $useThumbAsBanner
   *
   * @return \GuzzleHttp\Psr7\Response
   */
  public function postImageFragment(string $articleId, int $thumbFid, string $thumbAlt, int $bannerFid, string $bannerAlt, int $useThumbAsBanner): Response {
    $endpoint = sprintf(Settings::get('jcms_article_fragment_images_endpoint'), $articleId);
    $payload = $this->getPayLoad($thumbFid, $thumbAlt, $bannerFid, $bannerAlt, $useThumbAsBanner);
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
   * @param int    $thumbImageFid
   * @param string $thumbAlt
   * @param int    $bannerImageFid
   * @param string $bannerAlt
   * @param bool   $useThumbAsBanner
   *
   * @return string
   */
  public function getPayLoad(int $thumbImageFid, string $thumbAlt = '', int $bannerImageFid = 0, string $bannerAlt = '', bool $useThumbAsBanner = FALSE): string {
    if ($useThumbAsBanner) {
      $bannerImageFid = $thumbImageFid;
      $bannerAlt = $thumbAlt;
    }
    $images = [
      'image' => [
        'thumbnail' => [
          'alt' => $thumbAlt,
          'sizes' => [
            '16:9' => [
              250 => $this->getImageUri($thumbImageFid, 'crop_16x9_250x141'),
              500 => $this->getImageUri($thumbImageFid, 'crop_16x9_500x281'),
            ],
            '1:1' => [
              70 => $this->getImageUri($thumbImageFid, 'crop_1x1_70x70'),
              140 => $this->getImageUri($thumbImageFid, 'crop_1x1_140x140'),
            ],
          ],
        ],
      ],
    ];
    if ($bannerImageFid) {
      $images['image']['banner'] = [
        'alt' => $bannerAlt,
        'sizes' => [
          '2:1' => [
            900 => $this->getImageUri($bannerImageFid, 'crop_2x1_900x450'),
            1800 => $this->getImageUri($bannerImageFid, 'crop_2x1_1800x900'),
          ],
        ],
      ];
    }
    return json_encode($images);
  }

}
