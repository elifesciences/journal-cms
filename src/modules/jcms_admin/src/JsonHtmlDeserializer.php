<?php

namespace Drupal\jcms_admin;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class JsonHtmlDeserializer implements DenormalizerInterface
{

    public function denormalize($data, $class, $format = null, array $context = []) : string
    {
        $html = [];
        if (!empty($data['interviewee']['cv'])) {
            $cv = [];
            foreach ($data['interviewee']['cv'] as $item) {
                $cv[] = sprintf('<b>%s</b>: %s', $item['date'], $item['text']);
            }
            $data['content'][] = [
                'type' => 'section',
                'title' => sprintf('%s CV', $data['interviewee']['name']['preferred']),
                'content' => [
                    [
                        'type' => 'list',
                        'prefix' => 'bullet',
                        'items' => $cv,
                    ],
                ],
            ];
        }

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
                case 'button':
                    $html[] = sprintf('<elifebutton class="elife-button--default" data-href="%s">%s</elifebutton>', $item['uri'], $item['text']);
                    break;
                case 'youtube':
                    $html[] = sprintf('<oembed>https://www.youtube.com/watch?v=%s</oembed>', $item['id']);
                    break;
                case 'image':
                    $src = $this->convertUri($item['image']['uri']);
                    if (!empty($context['fids'][$src])) {
                        $fid = $context['fids'][$src]['fid'];
                        $src = $context['fids'][$src]['src'];
                    } else {
                        $fid = 1;
                    }
                    $class = [
                        'image',
                        ($item['inline'] ?? null) ? 'align-left' : 'align-center',
                    ];
                    $image = [
                        sprintf('<figure class="%s"><img alt="%s" data-fid="%d" data-uuid="UUID" src="%s" width="%d" height="%d" />', implode(' ', $class), ($item['image']['alt'] ?? ''), $fid, $src, $item['image']['size']['width'], $item['image']['size']['height']),
                    ];
                    if (!empty($item['title'])) {
                        $image[] = sprintf('<figcaption>%s</figcaption>', $item['title']);
                    }
                    $image[] = '</figure>';
                    $html[] = implode(PHP_EOL, $image);
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

    public function gatherImages(array $data) : array
    {
        $images = [];
        $content = $this->flattenHierarchy($data);
        foreach ($content as $item) {
            if ($item['type'] === 'image') {
                $images[] = $this->convertUri($item['image']['uri']);
            }
        }

        return $images;
    }

    private function convertUri(string $uri) : string
    {
        return preg_replace('/^.*journal\-cms\:/', 'public://iiif/', $uri);
    }

    private function flattenHierarchy(array $data, $depth = 1) : array
    {
        $items = [];

        foreach ($data as $item) {
            if ($item['type'] === 'figure') {
                $item = $item['assets'][0];
                unset($item['id']);
            }

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
                if ($item['type'] === 'image') {
                    if (!empty($item['label'])) {
                        $items[] = [
                            'type' => 'paragraph',
                            'text' => $item['label'],
                        ];
                    }
                    if (!empty($item['image']['attribution'])) {
                        foreach ($item['image']['attribution'] as $attribution) {
                            $items[] = [
                                'type' => 'paragraph',
                                'text' => $attribution,
                            ];
                        }
                    }
                }
            }
        }

        return $items;
    }

    public function supportsDenormalization($data, $type, $format = null) : bool
    {
        return is_array($data['content'] ?? null);
    }

}
