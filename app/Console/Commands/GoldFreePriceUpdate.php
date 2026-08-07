<?php

namespace App\Console\Commands;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class GoldFreePriceUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold:free';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update gold prices from free Iranian price providers (tgju.org + brsapi.ir)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $prices = [];

        $prices = array_merge($prices, $this->fromTgju());

        if (config('services.brsapi.key')) {
            $prices = array_merge($prices, $this->fromBrsapi());
        } else {
            $this->warn('brsapi.ir skipped (set BR_API_KEY env to enable this free provider)');
        }

        if (empty($prices['gold'])) {
            $this->error('No gold price fetched from any provider');
            return self::FAILURE;
        }

        // 24 karat fallback: derive from 18 karat (pure gold = 18k * 4/3)
        if (empty($prices['gold24'])) {
            $prices['gold24'] = round($prices['gold'] * 4 / 3);
            $this->warn('gold24 not found, derived from 18 karat gold');
        }

        foreach (['gold', 'gold24', 'silver', 'dollar'] as $key) {
            if (empty($prices[$key])) {
                continue;
            }
            $s = Setting::firstOrNew(['key' => $key]);
            $s->section = 'General';
            $s->title = $key === 'silver' ? __("Silver price") : __("Gold price");
            $s->type = 'TEXT';
            $s->ltr = true;
            $s->size = 12;
            $s->value = $prices[$key];
            $s->save();
        }

        GoldPriceUpdate::reprice($prices['gold']);

        $this->info('Gold and Silver prices updated from free providers: ' . json_encode($prices));

        return self::SUCCESS;
    }

    /**
     * Fetch prices from tgju.org profile pages (no API key required).
     * Prices are published in rial, the app stores toman (rial / 10).
     */
    private function fromTgju(): array
    {
        $result = [];
        $symbols = [
            'gold' => 'geram18',
            'gold24' => 'geram24',
            'silver' => 'silver_999',
            'dollar' => 'price_dollar_rl',
        ];

        $client = new Client([
            'timeout' => 20,
            'headers' => ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'],
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ]);

        foreach ($symbols as $key => $symbol) {
            try {
                $html = $client->get("https://www.tgju.org/profile/{$symbol}")->getBody()->getContents();
                if (preg_match('/نرخ فعلی<\/td>\s*<td class="text-left">([0-9,]+)<\/td>/u', $html, $m)) {
                    $result[$key] = (int)round(str_replace(',', '', $m[1]) / 10);
                    $this->info("tgju.org [{$symbol}] = {$result[$key]} toman");
                } else {
                    $this->warn("tgju.org [{$symbol}]: price not found in page");
                }
            } catch (\Throwable $e) {
                $this->warn("tgju.org [{$symbol}]: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Fetch prices from brsapi.ir free API (requires a free API key).
     * Prices are already in toman.
     */
    private function fromBrsapi(): array
    {
        $result = [];
        try {
            $client = new Client([
                'timeout' => 20,
                'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
            ]);
            $response = $client->get('https://Api.BrsApi.ir/Market/Gold_Currency.php', [
                'query' => ['key' => config('services.brsapi.key')],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            foreach ($data['gold'] ?? [] as $row) {
                if ($row['symbol'] === 'IR_GOLD_18K') {
                    $result['gold'] = (int)$row['price'];
                    $this->info('brsapi.ir [IR_GOLD_18K] = ' . $result['gold'] . ' toman');
                }
                if ($row['symbol'] === 'IR_GOLD_24K') {
                    $result['gold24'] = (int)$row['price'];
                    $this->info('brsapi.ir [IR_GOLD_24K] = ' . $result['gold24'] . ' toman');
                }
                // we might not know the exact symbol for silver on brsapi, but let's try IR_SILVER_999 or IR_SILVER
                if ($row['symbol'] === 'IR_SILVER' || $row['symbol'] === 'IR_SILVER_999') {
                    $result['silver'] = (int)$row['price'];
                    $this->info('brsapi.ir [' . $row['symbol'] . '] = ' . $result['silver'] . ' toman');
                }
            }
            foreach ($data['currency'] ?? [] as $row) {
                if ($row['symbol'] === 'USD') {
                    $result['dollar'] = (int)$row['price'];
                    $this->info('brsapi.ir [USD] = ' . $result['dollar'] . ' toman');
                }
            }
        } catch (\Throwable $e) {
            $this->warn('brsapi.ir: ' . $e->getMessage());
        }

        return $result;
    }
}
