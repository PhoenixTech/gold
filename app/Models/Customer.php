<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory,SoftDeletes;

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function main_tickets()
    {
        return $this->hasMany(Ticket::class)->whereNull('parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'customer_product');
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function isCheckoutReady(): bool
    {
        $hasName = $this->name !== null && trim((string) $this->name) !== '';
        $hasMobile = $this->mobile !== null && trim((string) $this->mobile) !== '';

        return $hasName && $hasMobile && $this->addresses()->exists();
    }

    public function missingCheckoutFields(): array
    {
        $missing = [];

        if ($this->name === null || trim((string) $this->name) === '') {
            $missing[] = __('Name');
        }
        if ($this->mobile === null || trim((string) $this->mobile) === '') {
            $missing[] = __('Mobile');
        }
        if (! $this->addresses()->exists()) {
            $missing[] = __('Address');
        }

        return $missing;
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'customer_product');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentator');
    }

    public function evaluations()
    {

        return Evaluation::where(function ($query) {
            $query->whereNull('evaluationable_type')
                ->whereNull('evaluationable_id');
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Customer::class)
                ->whereNull('evaluationable_id');
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Customer::class)
                ->where('evaluationable_id', $this->id);
        })->get();
    }

    public function avatar()
    {
        if ($this->avatar == null || trim($this->avatar) == '') {
            return asset('assets/default/unknown.svg');
        }

        return \Storage::url('customers/'.$this->avatar);
    }

    public function hasRole()
    {
        return false;
    }
}
