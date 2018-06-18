<?php

namespace Drupal\jcms_admin;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class JsonHtmlDeserializer implements DenormalizerInterface
{

    public function denormalize($data, $class, $format = null, array $context = []) : string
    {
        $html = [];
        $content = $this->flattenHierarchy($data['content']);
        foreach ($content as $item) {
            switch ($item['type']) {
                case 'section':
                    $html[] = sprintf('<h%d>%s</h%d>', $item['depth'], $item['title'], $item['depth']);
                    break;
                case 'paragraph':
                    $html[] = sprintf('<p>%s</p>', $item['text']);
                    break;
                case 'quote':
                    $html[] = sprintf('<blockquote>%s</blockquote>', $item['text'][0]['text']);
                    break;
                case 'table':
                    $html[] = $item['tables'][0];
                    break;
                case 'code':
                    $html[] = sprintf('<code>%s</code>', PHP_EOL.$item['code'].PHP_EOL);
                    break;
                case 'list':
                    $html[] = $this->flattenList($item['items'], $item['prefix']);
                    break;
            }
        }

        return implode(PHP_EOL, $html);
    }

    private function flattenList($items, $prefix = 'bullet', $delimiter = PHP_EOL) : string
    {
        $prefix = ($prefix === 'number') ? 'ol' : 'ul';
        $html = [];
        if (!empty($items)) {
            $html[] = sprintf('<%s>', $prefix);
            for ($i = 0; $i < count($items); $i++) {
                $item = $items[$i];
                if (is_string($item)) {
                    $html[] = sprintf('<li>%s</li>', $item);
                } elseif ($i > 0) {
                    $prev = array_pop($html);
                    $children = $this->flattenList($item[0]['items'], $item[0]['prefix'], '');
                    $html[] = preg_replace('~</li>$~', $children.'</li>', $prev);
                }
            }
            $html[] = sprintf('</%s>', $prefix);
        }

        return implode($delimiter, $html);
    }

    private function flattenHierarchy(array $data, $depth = 1) : array
    {
        $items = [];

        foreach ($data as $item) {
            if ($item['type'] === 'question') {
                $item['type'] = 'section';
                $item['title'] = $item['question'];
                $item['content'] = $item['answer'];

                unset($item['question']);
                unset($item['answer']);
            }

            if ($item['type'] === 'section') {
                $children = $this->flattenHierarchy($item['content'], $depth+1);
                unset($item['content']);
                $item['depth'] = $depth;
                $items[] = $item;
                $items = array_merge($items, $children);
            } else {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function supportsDenormalization($data, $type, $format = null) : bool
    {
        return in_array(($data['type'] ?? 'unknown'), ['blog-article']);
    }

}
