<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use DateTimeImmutable;
use DateTimeZone;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\jcms_article\ArticleCrud;
use Drupal\jcms_rest\Exception\JCMSBadRequestHttpException;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;

abstract class AbstractRestResourceBase extends ResourceBase {

  protected $defaultOptions = [
    'per-page' => 10,
    'page' => 1,
    'order' => 'desc',
    'start-date' => '2000-01-01',
    'end-date' => '2999-12-31',
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
        500 => '281',
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
      'published' => $this->formatDate($entity->getCreatedTime()),
    ];

    if ($entity->getRevisionCreationTime() > $entity->getCreatedTime()) {
      $defaults['updated'] = $this->formatDate($entity->getRevisionCreationTime());
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
    return \Drupal::service('date.formatter')->format($date, 'api_datetime');
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
        'start-date' => $request->query->get('start-date', $this->defaultOptions['start-date']),
        'end-date' => $request->query->get('end-date', $this->defaultOptions['end-date']),
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
            $loaded_style = ImageStyle::load(implode('_', $image_style));
            if ($loaded_style && $image_uri) {
              $image[$type]['sizes'][$ar][$width] = $loaded_style->buildUrl($image_uri);
            }
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
          case 'code':
            $result_item['code'] = $content_item->get('field_block_code')->getString();
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
   * Apply filter for date range by amending query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   * @param string $field
   */
  protected function filterDateRange(QueryInterface &$query, $field = 'created') {
    $start_date = DateTimeImmutable::createFromFormat('Y-m-d', $originalStartDate = $this->getRequestOption('start-date'), new DateTimeZone('Z'));
    $end_date = DateTimeImmutable::createFromFormat('Y-m-d', $originalEndDate = $this->getRequestOption('end-date'), new DateTimeZone('Z'));

    if (!$start_date || $start_date->format('Y-m-d') !== $this->getRequestOption('start-date')) {
      throw new JCMSBadRequestHttpException(t('Invalid start date'));
    } elseif (!$end_date || $end_date->format('Y-m-d') !== $this->getRequestOption('end-date')) {
      throw new JCMSBadRequestHttpException(t('Invalid end date'));
    }

    $start_date = $start_date->setTime(0, 0, 0);
    $end_date = $end_date->setTime(23, 59, 59);

    if ($end_date < $start_date) {
      throw new JCMSBadRequestHttpException(t('End date must be on or after start date'));
    }

    $query->condition($field, $start_date->getTimestamp(), '>=');
    $query->condition($field, $end_date->getTimestamp(), '<=');
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
    $crud_service = \Drupal::service('jcms_article.article_crud');
    return $crud_service->getArticle($node);
  }

  /**
   * Get subject list from articles array.
   *
   * @param array $articles
   * @return array
   */
  protected function subjectsFromArticles($articles) {
    $subjects = [];
    foreach ($articles as $article) {
      if (property_exists($article, 'subjects') && !empty($article->subjects)) {
        foreach ($article->subjects as $subject) {
          if (!isset($subjects[$subject->id])) {
            $subjects[$subject->id] = $subject;
          }
        }
      }
    }

    return array_values($subjects);
  }

  /**
   * Convert venue paragraph into array prepared for response.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $venue_field
   * @return array
   */
  protected function getVenue(Paragraph $venue_field) {
      $venue = [
        'name' => array_values(array_filter(preg_split("(\r\n?|\n)", $venue_field->get('field_block_title_multiline')->getString()))),
      ];

      // Venue address is optional.
      if ($venue_field->get('field_block_address')->count()) {
        $locale = 'en';
        /* @var \CommerceGuys\Addressing\AddressInterface $address  */
        $address = $venue_field->get('field_block_address')->first();
        $postal_label_formatter = \Drupal::service('address.postal_label_formatter');
        $postal_label_formatter->setOriginCountryCode('no_origin');
        $postal_label_formatter->setLocale($locale);
        $components = [
          'streetAddress' => ['getAddressLine1', 'getAddressLine2'],
          'locality' => ['getLocality', 'getDependentLocality'],
          'area' => ['getAdministrativeArea'],
        ];

        $venue['address'] = [
          'formatted' => explode("\n", $postal_label_formatter->format($address)),
          'components' => [],
        ];

        foreach ($components as $section => $methods) {
          $values = [];
          foreach ($methods as $method) {
            if ($value = $address->{$method}()) {
              $values[] = $value;
            }
          }

          if (!empty($values)) {
            $venue['address']['components'][$section] = $values;
          }
        }

        $country_repository = \Drupal::service('address.country_repository');
        $countries = $country_repository->getList($locale);
        $venue['address']['components']['country'] = $countries[$address->getCountryCode()];

        if ($postal_code = $address->getPostalCode()) {
          $venue['address']['components']['postalCode'] = $postal_code;
        }
        elseif ($postal_code = $address->getSortingCode()) {
          $venue['address']['components']['postalCode'] = $postal_code;
        }
      }

      return $venue;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   * @param \Drupal\Core\Field\FieldItemListInterface $related_field
   *
   * @return array|bool
   */
  public function getEntityQueueItem(EntityInterface $node, FieldItemListInterface $related_field) {
    if (empty($related_field->first()->get('entity')->getTarget())) {
      return FALSE;
    }

    /* @var Node $node */
    /* @var Node $related */
    $related = $related_field->first()->get('entity')->getTarget()->getValue();
    $rest_resource = [
      'blog_article' => new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'collection' => new CollectionListRestResource([], 'collection_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'event' => new EventListRestResource([], 'event_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'interview' => new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'labs_experiment' => new LabsExperimentListRestResource([], 'labs_experiment_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'podcast_episode' => new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger),
    ];

    $item_values = [
      'title' => $node->getTitle(),
      'image' => $this->processFieldImage($node->get('field_image'), TRUE, 'banner', TRUE),
    ];

    if ($related->getType() == 'article') {
      if ($article = $this->getArticleSnippet($related)) {
        $item_values['item'] = $article;
      }
    }
    else {
      if (!empty($rest_resource[$related->getType()])) {
        $item_values['item']['type'] = str_replace('_', '-', $related->getType());
        $item_values['item'] += $rest_resource[$related->getType()]->getItem($related);
      }
    }

    if (empty($item_values['item'])) {
      return FALSE;
    }

    return $item_values;
  }

}
