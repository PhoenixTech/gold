<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
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
     * The console command description.
     *
     * @var string
     */
    protected $description = 'gold price update';

    /**
     * Recalculate all product prices based on the new gold (18 karat) price.
     */
    public static function reprice(int $gold): void
    {
        $pros = Product::where('status', 1)->get();
        foreach ($pros as $pro) {
            $low = [];
            if ($pro->quantities()->count() > 0) {
                foreach ($pro->quantities as $q) {
                    $qdata = json_decode($q->data);
                    $q->price = CalcPrice($gold, $qdata->weight, $pro->wage) + $pro->addon;
                    $low[] = $q->price;
                    $q->save();
                }
                $pro->price = min($low);
            } else {
                $pro->price = 0;
            }
            if ((($pro->price * (int)getSetting('min')) / 100) < $pro->buy_price) {
                $pro->stock_status = 'OUT_STOCK';
            } else {
                $pro->stock_status = 'IN_STOCK';
            }
            $pro->save();
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new Client();
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
                $this->warn('Field not found for ' . $cfg['title'] . ' (tried: ' . implode(', ', $cfg['fields']) . ')');
                continue;
            }
            $s = Setting::firstOrNew(['key' => $key]);
            $s->section = 'General';
            $s->title = __("Gold price");
            $s->type = 'TEXT';
            $s->ltr = true;
            $s->size = 12;
            $s->value = $value;
            $s->save();
            $updated[$key] = $value;
        }

        if (isset($updated['gold'])) {
            static::reprice($updated['gold']);
            $this->info('Price updated successfully ' . json_encode($updated));
        } else {
            $this->error('Price update failed');
        }
    }
}
