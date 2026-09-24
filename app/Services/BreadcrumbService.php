<?php

namespace App\Services;

class BreadcrumbService
{
    public function markupBreadcrumbList(array $items): string
    {
        $json = [];
        $i = 0;
        foreach ($items as $name => $item) {
            $i++;
            $entry = [
                '@type' => 'ListItem',
                'position' => $i,
                'name' => $name,
            ];
            if (! empty($item)) {
                $entry['item'] = $item;
            }
            $json[] = $entry;
        }

        $jsonEncoded = json_encode($json);

        return <<<RESULT
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "BreadcrumbList",
          "itemListElement": $jsonEncoded
        }
    </script>
RESULT;
    }
}
