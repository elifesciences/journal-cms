<?php

namespace Drupal\jcms_admin;

use League\CommonMark\DocParser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class HtmlJsonSerializer implements NormalizerInterface
{

    private $htmlMardownNormalizer;
    private $markdownJsonNormalizer;

    public function __construct(HtmlMarkdownSerializer $htmlMardownNormalizer, MarkdownJsonSerializer $markdownJsonNormalizer)
    {
        $this->htmlMardownNormalizer = $htmlMardownNormalizer;
        $this->markdownJsonNormalizer = $markdownJsonNormalizer;
    }

    /**
     * @param string $object
     */
    public function normalize($object, $format = null, array $context = []) : array
    {
        $regexes = $context['regexes'] ?? [];
        $markdownContext = $jsonContext = $context;
        // Line breaks, curly brackets, italic and bold isn't handled correctly
        // in all instances. Side step this by using placeholders.
        $markdownContext['regexes'] = $regexes + [
            '~<br\s*/?>~' => '<linebreak></linebreak>',
            '~\{~' => '<curly>',
            '~\}~' => '</curly>',
            '~<(/?)(i|em)>~' => '<$1italic>',
            '~<(/?)(b|strong)>~' => '<$1bold>',
        ];
        $jsonContext['regexes'] = $regexes + [
            '~<linebreak></linebreak>~' => '<br />',
            '~<curly>~' => '{',
            '~</curly>~' => '}',
            '~<(/?)italic>~' => '<$1em>',
            '~<(/?)bold>~' => '<$1strong>',
        ];

        $markdown = $this->htmlMardownNormalizer->normalize($object, $format, $markdownContext);
        return $this->markdownJsonNormalizer->normalize($markdown, $format, $jsonContext);
    }

    public function supportsNormalization($data, $format = null) : bool
    {
        return is_string($data);
    }

}
