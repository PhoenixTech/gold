<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    const PENDING = 'PENDING';

    const SUCCESS = 'SUCCESS';

    const FAIL = 'FAIL';

    const CANCEL = 'CANCEL';

    protected $casts = [
        'meta' => 'array',
    ];

    public static $types = ['ONLINE', 'CHEQUE', 'CASH', 'CARD', 'CASH_ON_DELIVERY'];

    public static $status = ['PENDING', 'SUCCESS', 'FAIL', 'CANCEL'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }
}
