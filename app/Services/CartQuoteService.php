<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Quantity;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartQuoteService
{
    public const SESSION_KEY = 'cart_quote';

    public function __construct(protected ProductPriceCalculator $calculator) {}

    public function ttlMinutes(): int
    {
        $minutes = (int) getSetting('cart_quote_minutes');

        return $minutes > 0 ? $minutes : 30;
    }

    /**
     * @return array{quoted_at: int, expires_at: int, ttl_minutes: int, signature: string, prices: array<string, int>, remaining_seconds: int}
     */
    public function ensure(): array
    {
        $stored = Session::get(self::SESSION_KEY);
        $signature = $this->signature();

        if ($this->isUsable($stored, $signature)) {
            return $this->withRemaining($stored);
        }

        return $this->refresh();
    }

    /**
     * @return array{quoted_at: int, expires_at: int, ttl_minutes: int, signature: string, prices: array<string, int>, remaining_seconds: int}
     */
    public function refresh(): array
    {
        $now = now()->timestamp;
        $ttl = $this->ttlMinutes();

        $quote = [
            'quoted_at' => $now,
            'expires_at' => $now + ($ttl * 60),
            'ttl_minutes' => $ttl,
            'signature' => $this->signature(),
            'prices' => $this->livePrices(),
        ];

        Session::put(self::SESSION_KEY, $quote);

        return $this->withRemaining($quote);
    }

    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @return array{quoted_at: int, expires_at: int, ttl_minutes: int, signature: string, prices: array<string, int>, remaining_seconds: int}|null
     */
    public function current(): ?array
    {
        $stored = Session::get(self::SESSION_KEY);

        if (! is_array($stored)) {
            return null;
        }

        return $this->withRemaining($stored);
    }

    /**
     * Keep a valid in-window quote. Recalculate when the cart changed or the 30 minutes elapsed.
     *
     * @return array{quoted_at: int, expires_at: int, ttl_minutes: int, signature: string, prices: array<string, int>, remaining_seconds: int}
     */
    public function assertValidForCheckout(): array
    {
        $stored = Session::get(self::SESSION_KEY);

        if (is_array($stored) && (int) ($stored['expires_at'] ?? 0) > now()->timestamp) {
            return $this->withRemaining($stored);
        }

        $hadExpiredQuote = is_array($stored) && (int) ($stored['expires_at'] ?? 0) > 0;
        $this->refresh();

        if ($hadExpiredQuote) {
            throw ValidationException::withMessages([
                'price' => __('Gold prices were updated. Please review the new prices and create the invoice within :minutes minutes.', [
                    'minutes' => $this->ttlMinutes(),
                ]),
            ]);
        }

        return $this->ensure();
    }

    public function unitPrice(Product $product, ?Quantity $quantity): int
    {
        $quote = Session::get(self::SESSION_KEY);
        $key = $this->priceKey($product->id, $quantity?->id);

        if (is_array($quote) && isset($quote['prices'][$key])) {
            return (int) $quote['prices'][$key];
        }

        return $this->liveUnitPrice($product, $quantity);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function applyToLines(array $lines): array
    {
        if ($lines === []) {
            return $lines;
        }

        $prices = $this->ensure()['prices'];

        foreach ($lines as &$line) {
            $quantityId = $line['selected_quantity_id'] ?? ($line['q']['id'] ?? null);
            $key = $this->priceKey((int) $line['id'], $quantityId);

            if (isset($prices[$key])) {
                $line['price'] = $prices[$key];
            }

            if (isset($line['q']) && is_array($line['q'])) {
                $selectedKey = $this->priceKey((int) $line['id'], $line['q']['id'] ?? null);
                if (isset($prices[$selectedKey])) {
                    $line['q']['price'] = $prices[$selectedKey];
                    $line['price'] = $prices[$selectedKey];
                }
            }

            $pieces = $this->asArray($line['qz'] ?? null);
            if ($pieces === null) {
                continue;
            }

            foreach ($pieces as $index => $piece) {
                $piece = $this->asArray($piece);
                if ($piece === null) {
                    continue;
                }

                $pieceKey = $this->priceKey((int) $line['id'], $piece['id'] ?? null);
                if (isset($prices[$pieceKey])) {
                    $piece['price'] = $prices[$pieceKey];
                }

                $pieces[$index] = $piece;
            }

            $line['qz'] = $pieces;
        }
        unset($line);

        return $lines;
    }

    /**
     * @return array<string, int>
     */
    public function livePrices(): array
    {
        $cart = getCartData();
        $cardIds = $cart['cards'];
        $quantityIds = $cart['qs'];
        $prices = [];

        if ($cardIds === []) {
            return $prices;
        }

        $products = Product::query()
            ->whereIn('id', array_values(array_unique($cardIds)))
            ->with(['availableQuantities'])
            ->get()
            ->keyBy('id');

        foreach ($cardIds as $index => $productId) {
            $product = $products->get($productId);
            if ($product === null) {
                continue;
            }

            foreach ($product->availableQuantities as $quantity) {
                $prices[$this->priceKey($product->id, $quantity->id)] = $this->calculator->priceForQuantity($product, $quantity);
            }

            $selectedId = $quantityIds[$index] ?? null;
            if ($selectedId === null || $selectedId === '') {
                if ($product->availableQuantities->isEmpty()) {
                    $prices[$this->priceKey($product->id, null)] = $this->liveUnitPrice($product, null);
                }

                continue;
            }

            $selectedKey = $this->priceKey($product->id, (int) $selectedId);
            if (isset($prices[$selectedKey])) {
                continue;
            }

            $selected = Quantity::query()
                ->where('product_id', $product->id)
                ->whereKey($selectedId)
                ->first();

            if ($selected !== null) {
                $prices[$selectedKey] = $this->calculator->priceForQuantity($product, $selected);
            }
        }

        return $prices;
    }

    public function priceKey(int $productId, int|string|null $quantityId): string
    {
        if ($quantityId !== null && $quantityId !== '') {
            return 'q:'.(int) $quantityId;
        }

        return 'p:'.$productId;
    }

    public function liveUnitPrice(Product $product, ?Quantity $quantity): int
    {
        if ($quantity !== null) {
            return $this->calculator->priceForQuantity($product, $quantity);
        }

        return (int) $product->price;
    }

    protected function isUsable(mixed $quote, string $signature): bool
    {
        if (! is_array($quote)) {
            return false;
        }

        if (($quote['signature'] ?? '') !== $signature) {
            return false;
        }

        return (int) ($quote['expires_at'] ?? 0) > now()->timestamp;
    }

    protected function signature(): string
    {
        $cart = getCartData();

        return hash('sha256', json_encode([
            'cards' => array_map('intval', $cart['cards']),
            'qs' => array_map(function ($id) {
                if ($id === null || $id === '') {
                    return null;
                }

                return (int) $id;
            }, $cart['qs']),
        ]));
    }

    /**
     * @param  array{quoted_at: int, expires_at: int, ttl_minutes: int, signature: string, prices: array<string, int>}  $quote
     * @return array{quoted_at: int, expires_at: int, ttl_minutes: int, signature: string, prices: array<string, int>, remaining_seconds: int}
     */
    protected function withRemaining(array $quote): array
    {
        $quote['remaining_seconds'] = max(0, (int) $quote['expires_at'] - now()->timestamp);

        return $quote;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function asArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'resolve')) {
            $resolved = $value->resolve();

            return is_array($resolved) ? $resolved : null;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            $resolved = $value->toArray(request());

            return is_array($resolved) ? $resolved : null;
        }

        return null;
    }
}
