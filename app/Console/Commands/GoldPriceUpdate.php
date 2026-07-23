<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GoldPriceUpdate extends Command
{

    private $api = 'http://142.93.20.169:8001/gold';
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
     * Execute the console command.
     */
    public function handle()
    {
        //
        $client = new Client();
        $response = $client->request('GET', $this->api);
        $data = json_decode($response->getBody()->getContents());
        if (isset($data->price)) {
            $s = Setting::where('key', 'gold')->first();
            $s->value = round($data->price / 10);
            $s->save();
            $pros = Product::where('status', 1)->get();
            foreach ($pros as $pro) {
                $low = [];
                if ($pro->quantities()->count() > 0) {
                    foreach ($pro->quantities as $q) {
                        $data = json_decode($q->data);
                        $q->price = CalcPrice($s->value, $data->weight, $pro->wage) + $pro->addon;
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
            $this->info('Price updated successfully '. $s->value);
        }else{
            $this->error('Price update failed');
        }
//        Log::info('updated gold price');

    }
}
