<?php

namespace Drupal\jcms_admin;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Convert HTML to json.
 */
final class HtmlJsonSerializer implements NormalizerInterface {

  private $htmlMardownNormalizer;
  private $markdownJsonNormalizer;

  /**
   * Constructor.
   */
  public function __construct(HtmlMarkdownSerializer $htmlMardownNormalizer, MarkdownJsonSerializer $markdownJsonNormalizer) {
    $this->htmlMardownNormalizer = $htmlMardownNormalizer;
    $this->markdownJsonNormalizer = $markdownJsonNormalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) : array {
    $regexes = $context['regexes'] ?? [];
    $markdownContext = $jsonContext = $context;
    // Line breaks, curly brackets, italic and bold isn't handled correctly
    // in all instances. Side step this by using placeholders.
    $markdownContext['regexes'] = $regexes + [
      '~<br\s*/?>~' => '<linebreak></linebreak>',
      '~\{~' => '<curly>',
      '~\}~' => '</curly>',
      '~<(/?)(i|em)>~' => '!$1italic¡',
      '~<(/?)(b|strong)>~' => '!$1bold¡',
      '~`~' => '<backtick></backtick>',
    ];
    $jsonContext['regexes'] = $regexes + [
      '~<linebreak></linebreak>~' => '<br />',
      '~<curly>~' => '{',
      '~</curly>~' => '}',
      '~!(/?)italic¡~' => '<$1em>',
      '~!(/?)bold¡~' => '<$1strong>',
      '~<backtick></backtick>~' => '`',
    ];

    $markdown = $this->htmlMardownNormalizer->normalize($object, $format, $markdownContext);
    return $this->markdownJsonNormalizer->normalize($markdown, $format, $jsonContext);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) : bool {
    return is_string($data);
  }

}
