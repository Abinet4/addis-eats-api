<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone', 20)->nullable();
            $table->string('area')->index();
            $table->unsignedBigInteger('subtotal'); // santim
            $table->unsignedInteger('fee');         // santim
            $table->unsignedBigInteger('total');    // santim
            $table->string('status')->default('placed')->index();
            $table->string('payment_reference')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained();
            $table->string('dish_name');              // snapshot: survives a later rename
            $table->unsignedInteger('unit_price');    // snapshot: survives a later price change
            $table->unsignedSmallInteger('qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
