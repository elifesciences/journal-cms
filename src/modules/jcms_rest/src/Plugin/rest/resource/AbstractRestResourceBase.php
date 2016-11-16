<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;

abstract class AbstractRestResourceBase extends ResourceBase {

  protected $defaultOptions = [
    'per-page' => 10,
    'page' => 1,
    'order' => 'desc',
  ];

  protected static $requestOptions = [];

  protected $imageSizes = [
    'banner' => [
      '2:1' => [
        900 => '450',
        1800 => '900',
      ],
    ],
    'thumbnail' => [
      '16:9' => [
        250 => '141',
        500 => '282',
      ],
      '1:1' => [
        70 => '70',
        140 => '140',
      ],
    ],
  ];

  protected $defaultSortBy = 'created';

  /**
   * Process default values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $id
   * @param string|int $id_key
   * @return array
   */
  protected function processDefault(EntityInterface $entity, $id = NULL, $id_key = 'id') {

    $defaults = [
      $id_key => !is_null($id) ? $id : substr($entity->uuid(), -8),
      'title' => $entity->getTitle(),
    ];

    $sort_by = self::getSortBy();
    switch ($sort_by) {
      case 'created':
        $defaults['published'] = $this->formatDate($entity->getCreatedTime());
        break;
      case 'changed':
        $defaults['updated'] = $this->formatDate($entity->getRevisionCreationTime());
        break;
    }

    return $defaults;
  }

  /**
   * Format date.
   *
   * @param null|int $date
   * @return mixed
   */
  protected function formatDate($date = NULL) {
    $date = is_null($date) ? time() : $date;
    return \Drupal::service('date.formatter')->format($date, 'html_datetime');
  }

  /**
   * Set default request option.
   *
   * @param string $option
   * @param string|int|array $default
   */
  protected function setDefaultOption($option, $default) {
    $this->defaultOptions[$option] = $default;
  }

  /**
   * Returns an array of Drupal request options.
   *
   * @return array
   */
  protected function getRequestOptions() {
    if (empty($this::$requestOptions)) {
      $request = \Drupal::request();
      $this::$requestOptions = [
        'page' => (int) $request->query->get('page', $this->defaultOptions['page']),
        'per-page' => (int) $request->query->get('per-page', $this->defaultOptions['per-page']),
        'order' => $request->query->get('order', $this->defaultOptions['order']),
        'subject' => (array) $request->query->get('subject', $this->defaultOptions['subject']),
      ];
    }
    return $this::$requestOptions;
  }

  /**
   * Return named request option.
   *
   * @param string $option
   * @return int|string|array|NULL
   */
  protected function getRequestOption($option) {
    $requestOptions = $this->getRequestOptions();
    if ($requestOptions[$option]) {
      return $requestOptions[$option];
    }

    return NULL;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemListInterface $data
   * @param bool $required
   * @param array|string $size_types
   * @param bool $bump
   * @return array
   */
  protected function processFieldImage(FieldItemListInterface $data, $required = FALSE, $size_types = ['banner', 'thumbnail'], $bump = FALSE) {
    if ($required || $data->count()) {
      $image = $this->getImageSizes($size_types);

      foreach ($image as $type => $image_sizes) {
        $image_uri = $data->first()->get('entity')->getTarget()->get('uri')->getString();
        $image[$type]['alt'] = $data->first()->getValue()['alt'];
        foreach ($image_sizes['sizes'] as $ar => $sizes) {
          foreach ($sizes as $width => $height) {
            $image_style = [
              'crop',
              str_replace(':', 'x', $ar),
              $width . 'x' . $height,
            ];
            $image[$type]['sizes'][$ar][$width] = ImageStyle::load(implode('_', $image_style))->buildUrl($image_uri);
          }
        }
      }

      if ($bump && count($image) === 1) {
        $keys = array_keys($image);
        $image = $image[$keys[0]];
      }

      return $image;
    }

    return [];
  }

  protected function getImageSizes($size_types = ['banner', 'thumbnail']) {
    $sizes = [];
    $size_types = (array) $size_types;
    foreach ($size_types as $size_type) {
      if (isset($this->imageSizes[$size_type])) {
        $sizes[$size_type]['sizes'] = $this->imageSizes[$size_type];
      }
    }

    return $sizes;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemListInterface $data
   * @param bool $required
   * @return array
   */
  protected function processFieldContent(FieldItemListInterface $data, $required = FALSE) {
    $handle_paragraphs = function($content, $list_flag = FALSE) use (&$handle_paragraphs) {
      $result = [];
      foreach ($content as $paragraph) {
        $content_item = $paragraph->get('entity')->getTarget()->getValue();
        $content_type = $content_item->getType();
        $result_item = [
          'type' => $content_type,
        ];
        switch ($content_type) {
          case 'section':
            $result_item['title'] = $content_item->get('field_block_title')->getString();
            $result_item['content'] = $handle_paragraphs($content_item->get('field_block_content'));
            break;
          case 'paragraph':
            if ($content_item->get('field_block_html')->count()) {
              $result_item['text'] = $this->fieldValueFormatted($content_item->get('field_block_html'));
            }
            else {
              unset($result_item);
            }
            break;
          case 'question':
            $result_item['question'] = $content_item->get('field_block_title')->getString();
            $result_item['answer'] = $handle_paragraphs($content_item->get('field_block_question_answer'));
            break;
          case 'image':
            if ($image = $content_item->get('field_block_image')->first()) {
              $image = $content_item->get('field_block_image')->first();
              $result_item['alt'] = (string) $image->getValue()['alt'];
              $result_item['uri'] = file_create_url($image->get('entity')->getTarget()->get('uri')->getString());
              if ($content_item->get('field_block_html')->count()) {
                $result_item['title'] = $this->fieldValueFormatted($content_item->get('field_block_html'));
              }
            }
            else {
              unset($result_item);
            }
            break;
          case 'blockquote':
            $result_item['type'] = 'quote';
            $result_item['text'] = [
              [
                'type' => 'paragraph',
                'text' => $this->fieldValueFormatted($content_item->get('field_block_html')),
              ],
            ];
            break;
          case 'youtube':
            $result_item['id'] = $content_item->get('field_block_youtube_id')->getString();
            $result_item['width'] = (int) $content_item->get('field_block_youtube_width')->getString();
            $result_item['height'] = (int) $content_item->get('field_block_youtube_height')->getString();
            break;
          case 'table':
            $table_content = preg_replace('/\n/', '', $this->fieldValueFormatted($content_item->get('field_block_html')));
            if (preg_match("~(?P<table><table[^>]*>(?:.|\n)*?</table>)~", $table_content, $match)) {
              $result_item['tables'] = [$match['table']];
            }
            else {
              $result_item['tables'] = ['<table>' . $table_content . '</table>'];
            }
            break;
          case 'list':
            $result_item['prefix'] = $content_item->get('field_block_list_ordered')->getString() ? 'number' : 'bullet';
            $result_item['items'] = $handle_paragraphs($content_item->get('field_block_list_items'), TRUE);
            break;
          case 'list_item':
            $result_item = $this->fieldValueFormatted($content_item->get('field_block_html'));
            break;
          default:
            unset($result_item['type']);
        }

        if (!empty($result_item)) {
          if ($list_flag && $content_type != 'list_item') {
            $result_item = [$result_item];
          }
          $result[] = $result_item;
        }
      }

      return $result;
    };

    if ($required || $data->count()) {
      return $handle_paragraphs($data);
    }

    return [];
  }

  /**
   * @param \Drupal\Core\Field\FieldItemListInterface $field_subjects
   * @param bool $required
   * @return array
   */
  protected function processSubjects(FieldItemListInterface $field_subjects, $required = FALSE) {
    $subjects = [];
    if ($required || $field_subjects->count()) {
      /* @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($field_subjects->referencedEntities() as $term) {
        $subjects[] = [
          'id' => $term->get('field_subject_id')->getString(),
          'name' => $term->toLink()->getText(),
        ];
      }
    }
    return $subjects;
  }

  /**
   * Apply filter for subjects by amending query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   */
  protected function filterSubjects(QueryInterface &$query) {
    $subjects = $this->getRequestOption('subject');
    if (!empty($subjects)) {
      $query->condition('field_subjects.entity.field_subject_id.value', $subjects, 'IN');
    }
  }

  /**
   * Apply filter for page, per-page and order.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   * @param string
   */
  protected function filterPageAndOrder(QueryInterface &$query, $sort_by = NULL) {
    $sort_by = $this->setSortBy($sort_by);

    $request_options = $this->getRequestOptions();
    $query->range(($request_options['page'] - 1) * $request_options['per-page'], $request_options['per-page']);
    $query->sort($sort_by, $request_options['order']);
  }

  /**
   * Set the "sort by" field.
   *
   * @param string|null|bool $sort_by
   * @param bool $force
   * @return string
   */
  protected function setSortBy($sort_by = NULL, $force = FALSE) {
    static $cache = NULL;

    if ($force || is_null($cache)) {
      if (!is_null($sort_by)) {
        $cache = $sort_by;
      }
      else {
        $cache = $this->defaultSortBy;
      }
    }

    return $cache;
  }

  /**
   * Get the "sort by" field.
   *
   * @return string
   */
  protected function getSortBy() {
    return $this->setSortBy();
  }

  /**
   * Prepare the value from formatted field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $data
   * @return mixed|null
   */
  protected function fieldValueFormatted(FieldItemListInterface $data) {
    $view = $data->view();
    unset($view['#theme']);
    return render($view);
  }

  /**
   * Get the article snippet from article node.
   *
   * @param \Drupal\node\Entity\Node $node
   * @return array
   */
  protected function getArticleSnippet(Node $node) {
    // @todo - elife - nlisgo - output json values from node.
    return $this->dummyArticle($node->getTitle());
  }

  /**
   * Get a dummy article.
   *
   * @param string $article_id
   * @return array
   */
  protected function dummyArticle(string $article_id) {
    $random = new Random();

    // Generate a random name.
    $names = function($preferred_only = FALSE) use ($random) {
      $names = [ucfirst($random->word(rand(4, 9))), ucfirst($random->word(rand(4, 9)))];
      if ($preferred_only) {
        return implode(' ', $names);
      }
      else {
        return [
          'preferred' => implode(' ', $names),
          'index' => implode(', ', array_reverse($names)),
        ];
      }
    };

    // Select a single random item from array.
    $random_item = function ($array) {
      shuffle($array);
      return $array[0];
    };

    $content = [
      'type' => $random_item(['correction', 'editorial', 'feature', 'insight', 'research-advance', 'research-article', 'research-exchange', 'retraction', 'registered-report', 'replication-study', 'short-report', 'tools-resources']),
      'status' => $random_item(['poa', 'vor']),
      'id' => $article_id,
      'version' => rand(1, 3),
      'doi' => '10.7554/eLife.' . $article_id,
      'authorLine' => $names(TRUE) . $random_item(['', ' et al']),
      'title' => $random->sentences(3),
      'published' => $this->formatDate(),
      'statusDate' => $this->formatDate(),
      'volume' => rand(1, 5),
      'elocationId' => 'e' . $article_id,
      'pdf' => 'https://elifesciences.org/content/%d/e' . $article_id . '.pdf',
    ];

    // Insert the volume number into pdf url.
    $content['pdf'] = sprintf($content['pdf'], $content['volume']);

    // Optionally display impact statement.
    if (rand(0, 2) > 0) {
      $content['impactStatement'] = $random->sentences(4);
    }

    // Optionally display dummy image.
    if (rand(0, 2) === 0) {
      $content['image'] = $this->dummyThumbnail();
    }

    return $content;
  }

  /**
   * Get a dummy thumbnail.
   *
   * @return array
   */
  protected function dummyThumbnail() {
    $image = $this->getImageSizes('thumbnail');
    $image['thumbnail']['alt'] = '';
    foreach ($image['thumbnail']['sizes'] as $ar => $sizes) {
      foreach ($sizes as $width => $height) {
        $image['thumbnail']['sizes'][$ar][$width] = 'https://placehold.it/' . $width . 'x' . $height;
      }
    }

    return $image;
  }

}
