<?php

namespace App\Services;

use DOMDocument;

class TableOfContentsService
{
    public function generate(string $html): array
    {
        $doc = new DOMDocument;
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $toc = '';
        $tocItems = [];
        $lastH2 = '';
        $idCounter = 0;

        $headings = $doc->getElementsByTagName('*');

        foreach ($headings as $heading) {
            if (in_array($heading->nodeName, ['h2', 'h3'], true)) {
                $id = $this->generateHeadingId((string) $heading->nodeValue, $idCounter);
                $idCounter++;
                $heading->setAttribute('id', $id);

                if ($heading->nodeName === 'h2') {
                    $tocItems[] = [
                        'title' => $heading->nodeValue,
                        'id' => $id,
                        'children' => [],
                    ];
                    $lastH2 = $heading->nodeValue;
                } elseif ($heading->nodeName === 'h3' && $lastH2) {
                    $tocItems[count($tocItems) - 1]['children'][] = [
                        'title' => $heading->nodeValue,
                        'id' => $id,
                    ];
                }
            }
        }

        $toc .= $this->build($tocItems);

        return [$toc, $doc->saveHTML()];
    }

    public function generateHeadingId(string $text, int $counter): string
    {
        $id = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
        $id = trim($id, '-');

        if (empty($id)) {
            $id = 'heading';
        }

        return $id.'-'.$counter;
    }

    public function build(array $items): string
    {
        $html = '<ul>';
        foreach ($items as $item) {
            $html .= '<li>';
            $html .= '<a href="#'.$item['id'].'">'.$item['title'].'</a>';

            if (! empty($item['children'])) {
                $html .= $this->build($item['children']);
            }

            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}
