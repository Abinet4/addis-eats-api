<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Day 56: dishes belong to a restaurant, and are soft-deleted so old orders can still name them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
            $table->dropSoftDeletes();
        });
    }
};
