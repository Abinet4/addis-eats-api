<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
 * The dishes.restaurant text column duplicated restaurants.name and blocked the
 * natural relationship name $dish->restaurant. Forward-only fix: link every dish
 * to a restaurant row (creating one if a name has no row yet), then drop the text column.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('dishes')->whereNull('restaurant_id')->orderBy('id')->each(function (object $dish) {
            $slug = Str::slug($dish->restaurant);
            $id = DB::table('restaurants')->where('slug', $slug)->value('id')
                ?? DB::table('restaurants')->insertGetId([
                    'name' => $dish->restaurant, 'slug' => $slug, 'area' => $dish->area,
                    'phone' => '0900000000', 'created_at' => now(), 'updated_at' => now(),
                ]);
            DB::table('dishes')->where('id', $dish->id)->update(['restaurant_id' => $id]);
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn('restaurant');
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->string('restaurant')->default('');
        });

        DB::table('dishes')->orderBy('id')->each(function (object $dish) {
            $name = DB::table('restaurants')->where('id', $dish->restaurant_id)->value('name') ?? '';
            DB::table('dishes')->where('id', $dish->id)->update(['restaurant' => $name]);
        });
    }
};
