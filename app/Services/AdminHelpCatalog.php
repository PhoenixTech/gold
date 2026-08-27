<?php

namespace App\Services;

class AdminHelpCatalog
{
    /**
     * @return list<array{slug: string, title: string, icon: string, view: string}>
     */
    public function topics(): array
    {
        return [
            [
                'slug' => 'delivery',
                'title' => __('How motorcycle delivery works'),
                'icon' => 'ri-motorbike-line',
                'view' => 'admin.help.topics.delivery',
            ],
            [
                'slug' => 'gold-price',
                'title' => __('How gold price is calculated'),
                'icon' => 'ri-copper-coin-line',
                'view' => 'admin.help.topics.gold-price',
            ],
            [
                'slug' => 'checkout',
                'title' => __('How customer checkout works'),
                'icon' => 'ri-shopping-bag-3-line',
                'view' => 'admin.help.topics.checkout',
            ],
            [
                'slug' => 'shop-settings',
                'title' => __('Gold, checkout, and bank card options'),
                'icon' => 'ri-settings-4-line',
                'view' => 'admin.help.topics.shop-settings',
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, icon: string, view: string}|null
     */
    public function find(?string $slug): ?array
    {
        $topics = $this->topics();

        if ($slug === null || $slug === '') {
            return $topics[0];
        }

        foreach ($topics as $topic) {
            if ($topic['slug'] === $slug) {
                return $topic;
            }
        }

        return null;
    }
}
