<?php

namespace Drupal\jcms_ckeditor\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterPluginManager;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to caption and align CK Editor images.
 *
 * @Filter(
 *   id = "filter_ck_images",
 *   title = @Translation("CKEditor images"),
 *   description = @Translation("Caption and align CK Editor Images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterCkImages extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Filter manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterManager;

  /**
   * Constructs a new FilterCkImages.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Definition.
   * @param \Drupal\filter\FilterPluginManager $filter_manager
   *   Filter plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FilterPluginManager $filter_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->filterManager = $filter_manager ?: \Drupal::service('plugin.manager.filter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'img') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $html_filter = $this->filterManager->createInstance('filter_html', [
        'settings' => [
          'allowed_html' => '<a href hreflang target rel> <em> <strong> <cite> <code> <br>',
          'filter_html_help' => FALSE,
          'filter_html_nofollow' => FALSE,
        ],
      ]);
      foreach ($xpath->query('//img') as $node) {
        // Ignore img tags inside figures.
        $parent = $node->parentNode;
        $parent2 = $parent->parentNode;
        if ($parent->tagName === 'figure' || $parent2->tagName === 'figure') {
          continue;
        }
        // Read the data-caption attribute's value, then delete it.
        $caption = Html::escape($node->getAttribute('data-caption'));
        $node->removeAttribute('data-caption');

        if ($caption) {
          // Sanitize caption: decode HTML encoding, limit allowed HTML tags;
          // only allow inline tags that are allowed by default, plus <br>.
          $caption = Html::decodeEntities($caption);
          $raw_caption = $caption;
          $filtered_caption = $html_filter->process($caption, $langcode);
          $result->addCacheableDependency($filtered_caption);
          $caption = FilteredMarkup::create($filtered_caption->getProcessedText());
        }

        $align_mapping = [
          'left' => 'align-left',
          'center' => 'align-center',
          'right' => 'profile-left',
        ];
        $classes = ['image'];
        $align = $node->getAttribute('data-align');
        $node->removeAttribute('data-align');
        // $classes .= $node->getAttribute('class');
        $node->removeAttribute('class');

        // If one of the allowed alignments, add the corresponding class.
        if (isset($align_mapping[$align])) {
          $classes[] = $align_mapping[$align];
        }

        // Given the updated node and caption: re-render it with a caption, but
        // bubble up the value of the class attribute of the captioned element,
        // this allows it to collaborate with e.g. the filter_align filter.
        $node = ($node->parentNode->tagName === 'a') ? $node->parentNode : $node;
        $filter_caption = [
          '#theme' => 'filter_ckimages',
          // We pass the unsanitized string because this is a text format
          // filter, and after filtering, we always assume the output is safe.
          // @see \Drupal\filter\Element\ProcessedText::preRenderText()
          '#node' => FilteredMarkup::create($node->C14N()),
          '#caption' => $caption,
          '#classes' => implode(' ', $classes),
        ];
        $altered_html = \Drupal::service('renderer')->render($filter_caption);

        // Load altered HTML into a new DOMDocument and retrieve the element.
        $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
          ->item(0)
          ->childNodes;

        foreach ($updated_nodes as $updated_node) {
          // Import the updated node from the new DOMDocument into the original
          // one, importing also the child nodes of the updated node.
          $updated_node = $dom->importNode($updated_node, TRUE);
          $node->parentNode->insertBefore($updated_node, $node);
        }
        // Finally, remove the original data-caption node.
        $node->parentNode->removeChild($node);
      }

      // If <figure> wrapped in <div class="align-center">
      // then apply to <figure>.
      foreach ($xpath->query('//figure') as $node) {
        $parent = $node->parentNode;
        if ($parent->tagName === 'div' && $parent->getAttribute('class') === 'align-center') {
          $classes = $node->getAttribute('class');
          $classes .= ' align-center';
          $node->setAttribute('class', $classes);
          $parent->removeAttribute('class');
        }
      }

      $result->setProcessedText(Html::serialize($dom))
        ->addAttachments([
          'library' => [
            'filter/caption',
          ],
        ]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>Processes image tag to make them compatible CK Editor image handling</p>
      ');
    }
    else {
      return $this->t('Processes <img> tag for CK Editor image handling.');
    }
  }

}
