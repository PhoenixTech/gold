<?php

use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();

            // Translatable columns (spatie/laravel-translatable stores JSON).
            $table->text('name');
            $table->text('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('badge_text')->nullable();

            $table->enum('status', array_column(CampaignStatus::cases(), 'value'))
                ->default(CampaignStatus::Draft->value);
            $table->unsignedSmallInteger('priority')->default(10);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Which products auto-fill the tile: a list of App\Enums\Occasion values.
            $table->json('occasions')->nullable();
            // Which home page tabs the campaign is allowed to appear on.
            $table->json('metal_scope')->nullable();

            $table->unsignedSmallInteger('limit')->default(6);

            $table->string('image')->nullable();
            $table->string('mobile_image')->nullable();

            $table->text('canonical')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Resolver hot path: published campaigns inside a schedule window.
            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index('priority');
        });

        Schema::create('campaign_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['include', 'exclude'])->default('include');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['campaign_id', 'product_id']);
            $table->index(['campaign_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_product');
        Schema::dropIfExists('campaigns');
    }
};
