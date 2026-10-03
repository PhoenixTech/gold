<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function refund(Customer $customer, int $amount, ?Invoice $invoice, string $message, ?User $admin = null): Credit
    {
        return DB::transaction(function () use ($customer, $amount, $invoice, $message, $admin): Credit {
            $locked = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $locked->credit += $amount;
            $locked->save();
            $customer->credit = $locked->credit;

            $credit = new Credit;
            $credit->customer_id = $locked->id;
            $credit->invoice_id = $invoice?->id;
            $credit->amount = $amount;
            $credit->data = json_encode([
                'type' => 'refund',
                'user_id' => $admin?->id,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
            $credit->save();

            return $credit;
        });
    }
}
