<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\JCMSCheckIdTrait;
use Drupal\node\NodeInterface;
use function GuzzleHttp\Psr7\normalize_header;
use DateTimeImmutable;
use DateTimeZone;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jcms_rest\Exception\JCMSBadRequestHttpException;
use Drupal\jcms_rest\Exception\JCMSNotAcceptableHttpException;
use Drupal\jcms_rest\JCMSHtmlHelperTrait;
use Drupal\jcms_rest\JCMSImageUriTrait;
use Drupal\jcms_rest\PathMediaTypeMapper;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class AbstractRestResourceBase.
 */
abstract class AbstractRestResourceBase extends ResourceBase {

  use JCMSImageUriTrait;
  use JCMSHtmlHelperTrait;
  use JCMSCheckIdTrait;

  protected $defaultOptions = [
    'per-page' => 10,
    'page' => 1,
    'order' => 'desc',
    'subject' => [],
    'containing' => [],
    'start-date' => '2000-01-01',
    'end-date' => '2999-12-31',
    'use-date' => 'default',
    'show' => 'all',
    'sort' => 'date',
    'type' => NULL,
  ];

  protected static $requestOptions = [];

  protected $defaultSortBy = 'created';

  /**
   * Latest version.
   *
   * @var int
   */
  protected $latestVersion = 1;

  /**
   * Minimum version.
   *
   * @var int
   */
  protected $minVersion = 1;

  /**
   * Latest accepted version.
   *
   * @var int
   */
  protected $acceptVersion = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->acceptVersion = $this->getAcceptableVersion($this->latestVersion);
  }

  /**
   * Process default values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string|int|null $id
   *   ID.
   * @param string|int $id_key
   *   ID key.
   *
   * @return array
   *   Processed default snippet.
   */
  protected function processDefault(EntityInterface $entity, $id = NULL, $id_key = 'id') : array {
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
   */
  protected function formatDate(int $date = NULL) : string {
    $date = is_null($date) ? time() : $date;
    return \Drupal::service('date.formatter')->format($date, 'api_datetime');
  }

  /**
   * Set default request option.
   *
   * @param string $option
   *   Option.
   * @param string|int|array $default
   *   Default value.
   */
  protected function setDefaultOption(string $option, $default) {
    $this->defaultOptions[$option] = $default;
  }

  /**
   * Returns an array of Drupal request options.
   */
  protected function getRequestOptions() : array {
    if (empty($this::$requestOptions)) {
      $request = \Drupal::request();
      $this::$requestOptions = [
        'page' => (int) $request->query->get('page', $this->defaultOptions['page']),
        'per-page' => (int) $request->query->get('per-page', $this->defaultOptions['per-page']),
        'order' => $request->query->get('order', $this->defaultOptions['order']),
        'subject' => (array) $request->query->get('subject', $this->defaultOptions['subject']),
        'containing' => (array) $request->query->get('containing', $this->defaultOptions['containing']),
        'start-date' => $request->query->get('start-date', $this->defaultOptions['start-date']),
        'end-date' => $request->query->get('end-date', $this->defaultOptions['end-date']),
        'use-date' => $request->query->get('use-date', $this->defaultOptions['use-date']),
        'show' => $request->query->get('show', $this->defaultOptions['show']),
        'sort' => $request->query->get('sort', $this->defaultOptions['sort']),
        'type' => $request->query->get('type', $this->defaultOptions['type']),
      ];
    }

    if (!in_array($this::$requestOptions['order'], ['asc', 'desc'])) {
      throw new JCMSBadRequestHttpException(t('Invalid order option'));
    }

    return $this::$requestOptions;
  }

  /**
   * Return named request option.
   *
   * @param string $option
   *   Option.
   *
   * @return int|string|array|null
   *   Retrieved value.
   */
  protected function getRequestOption(string $option) {
    $requestOptions = $this->getRequestOptions();
    if ($requestOptions[$option]) {
      return $requestOptions[$option];
    }

    return NULL;
  }

  /**
   * Process field content.
   */
  public function processFieldContent(FieldItemListInterface $data, bool $required = FALSE) : array {
    $handle_paragraphs = function ($content, $list_flag = FALSE) use (&$handle_paragraphs) {
      $result = [];
      foreach ($content as $paragraph) {
        $content_item = $paragraph->get('entity')->getTarget()->getValue();
        $content_type = $content_item->getType();
        $result_item = [
          'type' => $content_type,
        ];
        // Only paragraph is supported for existing paragraph content fields.
        if ($content_type === 'paragraph') {
          if ($content_item->get('field_block_html')->count()) {
            // Split paragraphs in the UI into separate paragraph blocks.
            $texts = $this->splitParagraphs($this->fieldValueFormatted($content_item->get('field_block_html'), FALSE));
            foreach ($texts as $text) {
              if (!is_array($text)) {
                $text = trim($text);
                $loop_result_item = $result_item;
                if (!empty($text)) {
                  $loop_result_item['text'] = $text;
                  if ($list_flag && $content_type != 'list_item') {
                    $loop_result_item = [$loop_result_item];
                  }
                  $result[] = $loop_result_item;
                }
              }
              else {
                $result[] = $text;
              }
            }
          }

          unset($result_item);
        }
        else {
          unset($result_item['type']);
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
   * Process subjects.
   */
  protected function processSubjects(FieldItemListInterface $field_subjects, bool $required = FALSE) : array {
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
   */
  protected function filterSubjects(QueryInterface &$query) {
    $subjects = $this->getRequestOption('subject');
    if (!empty($subjects)) {
      $query->condition('field_subjects.entity.field_subject_id.value', $subjects, 'IN');
    }
  }

  /**
   * Apply filter for containing by amending query.
   */
  protected function filterContaining(
    QueryInterface &$query,
    string $field,
    array $permitted = [
      'article',
      'blog-article',
      'digest',
      'event',
      'interview',
    ]
  ) {
    $containing = $this->getRequestOption('containing');

    if (!empty($containing)) {
      $orCondition = $query->orConditionGroup();

      foreach ($containing as $item) {
        preg_match('~^(' . implode('|', $permitted) . ')/([a-z0-9-]+)$~', $item, $matches);

        if (empty($matches[1]) || empty($matches[2])) {
          throw new JCMSBadRequestHttpException(t('Invalid containing parameter'));
        }

        $andCondition = $query->andConditionGroup()
          ->condition($field . '.entity.type', str_replace('-', '_', $matches[1]));

        if (!$this->viewUnpublished()) {
          $andCondition->condition($field . '.entity.status', NodeInterface::PUBLISHED);
        }

        if ($matches[1] === 'article') {
          $andCondition = $andCondition->condition($field . '.entity.title', $matches[2], '=');
        }
        elseif ($matches[1] === 'digest') {
          $andCondition = $andCondition->condition($field . '.entity.field_digest_id.value', $matches[2], '=');
        }
        else {
          $andCondition = $andCondition->condition($field . '.entity.uuid', $matches[2], 'ENDS_WITH');
        }

        $orCondition = $orCondition->condition($andCondition);
      }

      $query->condition($orCondition);
    }
  }

  /**
   * Apply filter for page, per-page and order.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   Query to filter.
   * @param string|array|null $sort_by
   *   Sort by value(s).
   */
  protected function filterPageAndOrder(QueryInterface &$query, $sort_by = NULL) {
    $sort_bys = (array) $this->setSortBy($sort_by);

    if (!in_array($this->getRequestOption('sort'), ['date', 'page-views'])) {
      throw new JCMSBadRequestHttpException(t('Invalid sort option'));
    }

    $request_options = $this->getRequestOptions();
    $query->range(($request_options['page'] - 1) * $request_options['per-page'], $request_options['per-page']);
    foreach ($sort_bys as $sort_by) {
      $query->sort($sort_by, $request_options['order']);
    }
  }

  /**
   * Apply filter by show parameter: all, open or closed.
   *
   * @throws JCMSBadRequestHttpException
   */
  protected function filterShow(QueryInterface &$query, string $filterFieldName, bool $isTimeStamp = FALSE) {
    $show_option = $this->getRequestOption('show');
    $options = [
      'closed' => 'end-date',
      'open' => 'start-date',
    ];

    if (in_array($show_option, array_keys($options))) {
      self::$requestOptions[$options[$show_option]] = date('Y-m-d');
      $this->filterDateRange($query, $filterFieldName, NULL, $isTimeStamp);
    }
    elseif ($show_option != 'all') {
      throw new JCMSBadRequestHttpException(t('Invalid show option'));
    }
  }

  /**
   * Apply filter for date range by amending query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   Query.
   * @param string $default_field
   *   Default field.
   * @param string|null $published_field
   *   Published field.
   * @param bool $timestamp
   *   UNIX timestamp.
   */
  protected function filterDateRange(QueryInterface &$query, string $default_field = 'field_order_date.value', $published_field = 'created', bool $timestamp = TRUE) {
    $start_date = DateTimeImmutable::createFromFormat('Y-m-d', $originalStartDate = $this->getRequestOption('start-date'), new DateTimeZone('Z'));
    $end_date = DateTimeImmutable::createFromFormat('Y-m-d', $originalEndDate = $this->getRequestOption('end-date'), new DateTimeZone('Z'));
    $use_date = $this->getRequestOption('use-date');

    if (!$start_date || $start_date->format('Y-m-d') !== $this->getRequestOption('start-date')) {
      throw new JCMSBadRequestHttpException(t('Invalid start date'));
    }
    elseif (!$end_date || $end_date->format('Y-m-d') !== $this->getRequestOption('end-date')) {
      throw new JCMSBadRequestHttpException(t('Invalid end date'));
    }
    elseif (!in_array($use_date, ['published', 'default'])) {
      throw new JCMSBadRequestHttpException(t('Invalid use date'));
    }

    $start_date = $start_date->setTime(0, 0, 0);
    $end_date = $end_date->setTime(23, 59, 59);

    if ($end_date < $start_date) {
      throw new JCMSBadRequestHttpException(t('End date must be on or after start date'));
    }

    $field = (!is_null($published_field) && $use_date == 'published') ? $published_field : $default_field;

    if ($timestamp) {
      $query->condition($field, $start_date->getTimestamp(), '>=');
      $query->condition($field, $end_date->getTimestamp(), '<=');
    }
    else {
      $query->condition($field, $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
      $query->condition($field, $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=');
    }
  }

  /**
   * Set the "sort by" field.
   *
   * @param string|null|bool $sort_by
   *   Sort by value.
   * @param bool $force
   *   Force set.
   *
   * @return string
   *   Sort by value.
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
   */
  protected function getSortBy() : string {
    return $this->setSortBy();
  }

  /**
   * Prepare the value from formatted field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $data
   *   Field.
   * @param bool $simple
   *   Simple text field.
   * @param bool $split
   *   Split into array ov values.
   *
   * @return mixed
   *   Processed field value.
   */
  protected function fieldValueFormatted(FieldItemListInterface $data, bool $simple = TRUE, bool $split = FALSE) {
    $view = $data->view();
    unset($view['#theme']);
    $output = \Drupal::service('renderer')->renderPlain($view);
    $output = preg_replace('/(<img [^>]*src=\")(\/[^\"]+)/', '$1' . \Drupal::request()->getSchemeAndHttpHost() . '$2', $output);
    $output = str_replace(chr(194) . chr(160), ' ', $output);
    if ($simple) {
      $output = preg_replace('/\n/', '', $output);
    }

    if ($split) {
      return array_values(array_filter(preg_split("(\r\n?|\n)", preg_replace('/<br[^>]*>/', "\n", $output))));
    }
    else {
      return trim($output);
    }
  }

  /**
   * Get the article snippet from article node.
   *
   * @return mixed|bool
   *   Return article snippet, if found.
   */
  protected function getArticleSnippet(Node $node) {
    $crud_service = \Drupal::service('jcms_article.article_crud');
    return $crud_service->getArticle($node, $this->viewUnpublished());
  }

  /**
   * Get the digest snippet from digest node.
   *
   * @return mixed|bool
   *   Return digest snippet, if found.
   */
  protected function getDigestSnippet(Node $node) {
    $crud_service = \Drupal::service('jcms_digest.digest_crud');
    return ['type' => 'digest'] + $crud_service->getDigest($node);
  }

  /**
   * Get subject list from articles array.
   */
  protected function subjectsFromArticles(array $articles = NULL) : array {
    $subjects = [];
    foreach ($articles as $article) {
      if (!empty($article['subjects'])) {
        foreach ($article['subjects'] as $subject) {
          if (!isset($subjects[$subject['id']])) {
            $subjects[$subject['id']] = $subject;
          }
        }
      }
    }

    return array_values($subjects);
  }

  /**
   * Convert venue paragraph into array prepared for response.
   */
  protected function getVenue(Paragraph $venue_field) : array {
    $venue = [
      'name' => array_values(array_filter(preg_split("(\r\n?|\n)", $venue_field->get('field_block_title_multiline')->getString()))),
    ];

    // Venue address is optional.
    if ($venue_field->get('field_block_address')->count()) {
      $locale = 'en';
      /* @var \CommerceGuys\Addressing\AddressInterface $address  */
      $address = $venue_field->get('field_block_address')->first();
      $postal_label_formatter = \Drupal::service('address.postal_label_formatter');
      $components = [
        'streetAddress' => ['getAddressLine1', 'getAddressLine2'],
        'locality' => ['getLocality', 'getDependentLocality'],
        'area' => ['getAdministrativeArea'],
      ];

      $venue['address'] = [
        'formatted' => explode("\n", $postal_label_formatter->format($address, [
          'origin_country' => 'no_origin',
          'locale' => 'en',
        ])),
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
   * Prepare extended collection item which can be added the snippet.
   */
  protected function extendedCollectionItem(EntityInterface $node) {
    $item = [];

    // Summary is optional.
    if ($summary = $this->processFieldContent($node->get('field_summary'))) {
      $item['summary'] = $summary;
    }

    // Collection content is required.
    $item['content'] = [];

    $blog_article_rest_resource = new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $event_rest_resource = new EventListRestResource([], 'event_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $interview_rest_resource = new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger);

    foreach (['content' => 'field_collection_content', 'relatedContent' => 'field_collection_related_content'] as $k => $field) {
      foreach ($node->get($field)->referencedEntities() as $content) {
        /* @var Node $content */
        if ($content->isPublished() || $this->viewUnpublished()) {
          switch ($content->getType()) {
            case 'blog_article':
              $item[$k][] = ['type' => 'blog-article'] + $blog_article_rest_resource->getItem($content);
              break;

            case 'event':
              $item[$k][] = ['type' => 'event'] + $event_rest_resource->getItem($content);
              break;

            case 'interview':
              $item[$k][] = ['type' => 'interview'] + $interview_rest_resource->getItem($content);
              break;

            case 'article':
              if ($snippet = $this->getArticleSnippet($content)) {
                $item[$k][] = $snippet;
              }
              break;

            case 'digest':
              if ($snippet = $this->getDigestSnippet($content)) {
                $item[$k][] = $snippet;
              }

            default:
          }
        }
      }
    }

    // Podcasts are optional.
    if ($node->get('field_collection_podcasts')->count()) {
      $item['podcastEpisodes'] = [];
      $podcast_rest_resource = new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger);
      foreach ($node->get('field_collection_podcasts')->referencedEntities() as $podcast) {
        /* @var Node $podcast */
        if ($podcast->isPublished() || $this->viewUnpublished()) {
          $item['podcastEpisodes'][] = $podcast_rest_resource->getItem($podcast);
        }
      }
    }
    return $item;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Entity.
   * @param \Drupal\Core\Field\FieldItemListInterface $related_field
   *   Field.
   * @param bool $image
   *   Has image.
   *
   * @return array|bool
   *   Has image.
   */
  public function getEntityQueueItem(EntityInterface $node, FieldItemListInterface $related_field, bool $image = TRUE) {
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
      'podcast_chapter' => new PodcastEpisodeItemRestResource([], 'podcast_episode_item_rest_resource', [], $this->serializerFormats, $this->logger),
      'press_package' => new PressPackageListRestResource([], 'press_package_list_rest_resource', [], $this->serializerFormats, $this->logger),
    ];

    $item_values = [
      'title' => $node->getTitle(),
    ];

    if ($image) {
      $item_values['image'] = $this->processFieldImage($node->get('field_image'), TRUE, 'banner', TRUE);
      $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
      if (!empty($attribution)) {
        $item_values['image']['attribution'] = $attribution;
      }
    }

    if ($related->getType() == 'article') {
      if ($article = $this->getArticleSnippet($related)) {
        $item_values['item'] = $article;
      }
    }
    elseif ($related->getType() == 'digest') {
      if ($digest = $this->getDigestSnippet($related)) {
        $item_values['item']['type'] = 'digest';
        $item_values['item'] += $digest;
      }
    }
    elseif ($related->getType() == 'podcast_chapter') {
      $item_values['item']['type'] = 'podcast-episode-chapter';
      $item_values['item'] += $rest_resource[$related->getType()]->getChapterItem($related, 0, TRUE);
    }
    else {
      if (!empty($rest_resource[$related->getType()])) {
        $type = ($related->getType() == 'labs_experiment') ? 'labs-post' : str_replace('_', '-', $related->getType());
        $item_values['item']['type'] = $type;
        $item_values['item'] += $rest_resource[$related->getType()]->getItem($related);
      }
    }

    if (empty($item_values['item'])) {
      return FALSE;
    }

    return $item_values;
  }

  /**
   * Returns the endpoint from the rest resource "canonical" annotation.
   *
   * @throws \Exception
   */
  public function getEndpoint(): string {
    $r = new \ReflectionClass(static::class);
    $annotation = $r->getDocComment();
    preg_match("/\"canonical\" = \"(.+)\"/", $annotation, $endpoint);
    if (!$endpoint) {
      throw new \Exception('Canonical URI not found in rest resource.');
    }
    return $endpoint[1];
  }

  /**
   * Gets the content type for the current rest resource.
   *
   * @throws \Exception
   *
   * @todo Handle this in the response object optionally.
   */
  public function getContentType(): string {
    $endpoint = $this->getEndpoint();
    $mapper = new PathMediaTypeMapper();
    $content_type = $mapper->getMediaTypeByPath($endpoint);
    if (!$content_type) {
      throw new \Exception('Content type not found for specified rest resource.');
    }
    return $content_type . ';version=' . $this->acceptVersion;
  }

  /**
   * Return the acceptable version.
   */
  public function getAcceptableVersion(int $latest_version = 1) : int {
    $endpoint = $this->getEndpoint();
    $mapper = new PathMediaTypeMapper();
    $content_type = $mapper->getMediaTypeByPath($endpoint);
    $request = \Drupal::request();
    $acceptable_version = $latest_version;

    $accept_headers = AcceptHeader::fromString($request->headers->get('Accept'))->all();
    if (!empty($accept_headers[$content_type]) && $accept_headers[$content_type]->hasAttribute('version')) {
      $acceptable_version = (int) $accept_headers[$content_type]->getAttribute('version', $latest_version);
    }

    if ($acceptable_version < $this->minVersion || $acceptable_version > $latest_version) {
      throw new JCMSNotAcceptableHttpException(sprintf('%s; version=%s is not supported', $content_type, $acceptable_version));
    }
    else {
      return $acceptable_version;
    }
  }

  /**
   * Determine if the request user can view unpublished content.
   */
  public function viewUnpublished() : bool {
    static $view_unpublished = NULL;

    if (is_null($view_unpublished)) {
      $view_unpublished = $this->consumerGroup('view-unpublished-content');
    }

    return $view_unpublished;
  }

  /**
   * Determine if the request user can view restricted content.
   */
  public function viewRestricted(string $content = 'content') : bool {
    static $view_restricted = [];

    if (!isset($view_restricted[$content])) {
      $view_restricted[$content] = $this->consumerGroup('view-restricted-' . $content);
    }

    return $view_restricted[$content];
  }

  /**
   * Determine if X-Consumer-Group header set.
   */
  public function consumerGroup(string $group) : bool {
    static $groups = NULL;

    if (is_null($groups)) {
      $request = \Drupal::request();
      $groups = normalize_header($request->headers->get('X-Consumer-Groups', 'user'));
    }

    return in_array($group, $groups);
  }

  /**
   * Process people names.
   */
  public function processPeopleNames(string $preferred_name, FieldItemListInterface $index_name) : array {
    return [
      'preferred' => $preferred_name,
      'index' => ($index_name->count()) ? $index_name->getString() : preg_replace('/^(?P<first_names>.*)\s+(?P<last_name>[^\s]+)$/', '$2, $1', $preferred_name),
    ];
  }

  /**
   * Process people names.
   */
  public function processPeopleNamesSplit(string $surname, string $given, FieldItemListInterface $preferred_name, FieldItemListInterface $index_name) : array {
    return array_filter([
      'surname' => $surname,
      'givenNames' => $given,
      'preferred' => ($preferred_name->count()) ? $preferred_name->getString() : implode(' ', array_filter([$given, $surname])),
      'index' => ($index_name->count()) ? $index_name->getString() : implode(', ', array_filter([$surname, $given])),
    ]);
  }

  /**
   * Process response.
   */
  protected function processResponse(Response $response) {
    if ($warning_text = $this->getWarningText()) {
      $warning = sprintf('299 api.elifesciences.org "%s"', $warning_text);
      $response->headers->add(['Warning' => $warning]);
    }
  }

  /**
   * Get warning text.
   *
   * @return string|null
   *   Warning text, if available.
   */
  protected function getWarningText() {
    if ($this->acceptVersion < $this->latestVersion) {
      return sprintf('Deprecation: Support for version %d will be removed', $this->acceptVersion);
    }
  }

}
