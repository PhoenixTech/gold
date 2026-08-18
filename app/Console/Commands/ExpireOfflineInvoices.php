<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Console\Command;

class ExpireOfflineInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offline:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fail offline invoices that passed the payment deadline without a receipt upload';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deadline = now()->subHours(Invoice::offlinePaymentHours());
        $expired = 0;

        Invoice::query()
            ->where('status', Invoice::AWAITING_PAYMENT)
            ->where('created_at', '<', $deadline)
            ->whereHas('payments', function ($query) {
                $query->where('type', 'CARD')->where('status', Payment::PENDING);
            })
            ->whereDoesntHave('paymentReceipts')
            ->chunkById(100, function ($invoices) use (&$expired) {
                foreach ($invoices as $invoice) {
                    $invoice->expireOfflinePayment();
                    $expired++;
                }
            });

        $this->info("Expired {$expired} offline invoice(s).");

        return self::SUCCESS;
    }
}
