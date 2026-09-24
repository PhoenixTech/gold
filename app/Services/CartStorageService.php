<?php

namespace App\Services;

use App\Http\Resources\ProductCardCollection;
use App\Http\Resources\QuantityResource;
use App\Models\Product;
use App\Models\Quantity;
use Illuminate\Support\Facades\Cookie;

class CartStorageService
{
    public function getCartData(): array
    {
        $cards = [];
        $qs = [];

        $cookieCard = Cookie::get('card') ?? (request()->hasCookie('card') ? request()->cookie('card') : null);
        $cookieQ = Cookie::get('q') ?? (request()->hasCookie('q') ? request()->cookie('q') : null);

        if (is_array($cookieCard)) {
            $cards = $cookieCard;
        } elseif (is_string($cookieCard) && trim($cookieCard) !== '') {
            $decoded = json_decode($cookieCard, true);
            if (is_array($decoded)) {
                $cards = $decoded;
            } else {
                $decoded = json_decode(urldecode($cookieCard), true);
                if (is_array($decoded)) {
                    $cards = $decoded;
                }
            }
        }

        if (is_array($cookieQ)) {
            $qs = $cookieQ;
        } elseif (is_string($cookieQ) && trim($cookieQ) !== '') {
            $decoded = json_decode($cookieQ, true);
            if (is_array($decoded)) {
                $qs = $decoded;
            } else {
                $decoded = json_decode(urldecode($cookieQ), true);
                if (is_array($decoded)) {
                    $qs = $decoded;
                }
            }
        }

        if (empty($cards) && auth('customer')->check()) {
            $customer = auth('customer')->user();
            if ($customer && ! empty($customer->card)) {
                $data = is_array($customer->card) ? $customer->card : json_decode($customer->card, true);
                if (is_array($data)) {
                    if (isset($data['cards']) && is_array($data['cards'])) {
                        $cards = $data['cards'];
                        $qs = $data['quantities'] ?? [];
                    } else {
                        $cards = $data;
                    }
                }
            }
        }

        return [
            'cards' => array_values(array_filter((array) $cards, fn ($v) => $v !== null && $v !== '')),
            'qs' => array_values((array) $qs),
        ];
    }

    public function getCardItems(): array
    {
        $cart = $this->getCartData();
        $cardIds = $cart['cards'];
        $quantityIds = $cart['qs'];

        if (empty($cardIds)) {
            return [];
        }

        $products = Product::query()
            ->whereIn('id', array_values(array_unique($cardIds)))
            ->with(['availableQuantities'])
            ->get()
            ->keyBy('id');

        $quantityIdsClean = array_values(array_filter($quantityIds));
        $pieces = ! empty($quantityIdsClean)
            ? Quantity::query()->whereIn('id', $quantityIdsClean)->get()->keyBy('id')
            : collect();

        $lines = [];
        foreach ($cardIds as $index => $productId) {
            $product = $products->get($productId);
            if ($product === null) {
                continue;
            }

            $line = (new ProductCardCollection($product))->resolve();
            $selectedId = $quantityIds[$index] ?? null;
            $selected = null;

            if ($selectedId !== null && $selectedId !== '') {
                $piece = $pieces->get($selectedId);

                if ($piece !== null) {
                    $selected = (new QuantityResource($piece))->resolve();
                    $line['price'] = $piece->price;
                }
            }

            $line['q'] = $selected;
            $line['selected_quantity_id'] = $selected['id'] ?? null;
            $lines[] = $line;
        }

        return app(CartQuoteService::class)->applyToLines($lines);
    }

    public function getCardCount(): int
    {
        $cart = $this->getCartData();

        return count($cart['cards']);
    }
}
