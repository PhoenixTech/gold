<?php

namespace App\Console\Commands;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GoldPriceUpdate extends Command
{

    private $api = 'https://gold.xstack.ir/v1/gold';
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
        if (isset($data->price_toman)) {
            $s = Setting::where('key', 'gold')->first();
            $s->value = $data->price_toman;
            $s->save();
            $this->info('Price updated successfully '. $s->value);
        }else{
            $this->error('Price update failed');
        }
//        Log::info('updated gold price');

    }
}
