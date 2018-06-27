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
        $markdown = $this->htmlMardownNormalizer->normalize($object, $format, $context);
        return $this->markdownJsonNormalizer->normalize($markdown, $format, $context);
    }

    public function supportsNormalization($data, $format = null) : bool
    {
        return is_string($data);
    }

}
