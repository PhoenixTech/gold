<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\ProductPriceCalculator;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class GoldPriceUpdate extends Command
{
    private $api = 'http://194.33.105.71:8000/gold';

    private array $prices = [
        'gold' => [
            'title' => '18 karat gold',
            'fields' => ['price', 'geram18', 'price18', 'g18'],
        ],
        'gold24' => [
            'title' => '24 karat gold',
            'fields' => ['geram24', 'price24', 'g24'],
        ],
        'dollar' => [
            'title' => 'dollar',
            'fields' => ['price_dollar', 'dollar', 'usd', 'price_usd'],
        ],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'gold price update';

    /**
     * Recalculate product prices. Optionally limit to a metal type.
     */
    public static function reprice(?string $metalType = null): void
    {
        app(ProductPriceCalculator::class)->repriceProducts($metalType);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new Client;
        $response = $client->request('GET', $this->api);
        $data = json_decode($response->getBody()->getContents());

        $updated = [];
        foreach ($this->prices as $key => $cfg) {
            $value = null;
            foreach ($cfg['fields'] as $field) {
                if (isset($data->$field)) {
                    $value = round($data->$field / 10);
                    break;
                }
            }
            if ($value === null) {
                $this->warn('Field not found for '.$cfg['title'].' (tried: '.implode(', ', $cfg['fields']).')');

                continue;
            }
            $s = Setting::firstOrNew(['key' => $key]);
            $s->section = 'General';
            $s->title = __('Gold price');
            $s->type = 'TEXT';
            $s->ltr = true;
            $s->size = 12;
            $s->value = $value;
            $s->save();
            $updated[$key] = $value;
        }

        if (isset($updated['gold'])) {
            static::reprice('gold');
            $this->info('Price updated successfully '.json_encode($updated));
        } else {
            $this->error('Price update failed');
        }
    }
}
