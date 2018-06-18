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
            }
        }

        return implode(PHP_EOL, $html);
    }

    private function flattenList($items, $type = 'bullet') : string
    {
        $list = ($type === 'number') ? 'ol' : 'li';
        $html = [];
        if (!empty($item)) {
            $html[] = sprintf('<%s>', $list);
            for ($i = 0; $i < count($items); $i++) {
            }
            $html[] = sprintf('</%s>', $list);
        }
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
