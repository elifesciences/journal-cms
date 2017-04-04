<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

trait JMCSGetRemoteFileTrait {
  function getFile($filename, $options = []) {
    $guzzle = new Client();
    try {
      $options += [
        'timeout' => 13,
        'http_errors' => FALSE,
      ];
      $response = $guzzle->get($filename, $options);
      if ($response->getStatusCode() == 200) {
        return $response->getBody()->getContents();
      }

      error_log(sprintf("File %s didn't download. (return code %d)", $filename, $response->getStatusCode()));
      return FALSE;
    }
    catch (ConnectException $e) {
      error_log(sprintf("File %s didn't download. (%s)", $filename, $e->getMessage()));
    }
  }

  /**
   * Lookup images on dedicated public S3 bucket.
   *
   * @param $prefix
   * @return array
   */
  private function s3ImageSearch($prefix) {
    if ($local_results = glob(DRUPAL_ROOT . '/../scripts/legacy_cms_files_alt/' . $prefix . '*')) {
      return $local_results;
    }

    $s3 = \Drupal::service('jcms_migrate.s3_client');
    $bucket = Settings::get('jcms_migrate_legacy_cms_images_bucket');
    $objects = $s3->getIterator('ListObjects', ['Bucket' => $bucket, 'Prefix' => $prefix]);
    $results = [];
    foreach ($objects as $object) {
      $results[] = $s3->getObjectUrl($bucket, $object['Key']);
    }
    return $results;
  }
}
