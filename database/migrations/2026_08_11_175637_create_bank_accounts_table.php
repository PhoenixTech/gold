<?php

use App\Models\BankAccount;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('card_number')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->boolean('is_active')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        if (BankAccount::query()->count() === 0 && Schema::hasTable('settings')) {
            $cardNumber = Setting::query()->where('key', 'bank_card_number')->value('raw')
                ?? Setting::query()->where('key', 'bank_card_number')->value('value');
            $iban = Setting::query()->where('key', 'bank_sheba')->value('raw')
                ?? Setting::query()->where('key', 'bank_sheba')->value('value');
            $holder = Setting::query()->where('key', 'bank_account_name')->value('raw')
                ?? Setting::query()->where('key', 'bank_account_name')->value('value');

            $cardNumber = is_string($cardNumber) ? $cardNumber : '';
            $iban = is_string($iban) ? $iban : '';
            $holder = is_string($holder) ? $holder : '';

            if ($cardNumber !== '' || $iban !== '' || $holder !== '') {
                BankAccount::query()->create([
                    'card_number' => $cardNumber !== '' ? $cardNumber : null,
                    'account_number' => null,
                    'iban' => $iban !== '' ? $iban : null,
                    'bank_name' => __('Bank'),
                    'account_holder_name' => $holder !== '' ? $holder : __('Account holder'),
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
