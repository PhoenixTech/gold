<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class ManualInvoiceDraft
{
    public const SESSION_KEY = 'admin.manual_invoice.draft';

    public function get(): array
    {
        $stored = Session::get(self::SESSION_KEY);

        return array_merge(self::blank(), is_array($stored) ? $stored : []);
    }

    public function put(array $draft): void
    {
        Session::put(self::SESSION_KEY, array_merge(self::blank(), $draft));
    }

    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public static function blank(): array
    {
        return [
            'customer' => null,
            'quantity_ids' => [],
            'payments' => [],
            'handover' => true,
            'note' => null,
        ];
    }
}
