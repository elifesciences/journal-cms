<?php

namespace Drupal\jcms_admin;

use League\CommonMark\DocParser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class HtmlJsonSerializer implements NormalizerInterface
{

    private $htmlMardownNormalizer;
    private $markdownJsonNormalizer;
    private $docParser;

    public function __construct(HtmlMarkdownSerializer $htmlMardownNormalizer, MarkdownJsonSerializer $markdownJsonNormalizer, DocParser $docParser)
    {
        $this->htmlMardownNormalizer = $htmlMardownNormalizer;
        $this->markdownJsonNormalizer = $markdownJsonNormalizer;
        $this->docParser = $docParser;
    }

    /**
     * @param string $object
     */
    public function normalize($object, $format = null, array $context = []) : array
    {
        $markdown = $this->htmlMardownNormalizer->normalize($object, $format, $context);
        return $this->markdownJsonNormalizer->normalize($this->docParser->parse($markdown), $format, $context);
    }

    public function supportsNormalization($data, $format = null) : bool
    {
        return is_string($data);
    }

}
