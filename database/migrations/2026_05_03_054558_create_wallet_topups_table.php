<?php

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
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->morphs('topupable');
            $table->decimal('amount', 20, 2)->default(0)->nullable();
            $table->decimal('vendor_fee_amount', 20, 2)->default(0)->nullable();
            $table->decimal('vendor_fee_percentage', 20, 2)->default(0)->nullable();
            $table->decimal('surcharge_amount', 20, 2)->default(0)->nullable();
            $table->decimal('surcharge_percentage', 20, 2)->default(0)->nullable();
            $table->decimal('total_fee', 20, 2)->default(0)->nullable();
            $table->decimal('net_amount', 20, 2)->default(0)->nullable();
            $table->enum('fee_charge_to', ['customer', 'merchant'])->default('merchant');
            $table->ipAddress()->nullable();
            $table->string('agent')->nullable();
            $table->string('status')->nullable()->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
