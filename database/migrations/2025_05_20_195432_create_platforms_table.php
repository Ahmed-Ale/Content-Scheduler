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
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->timestamps();
        });

        DB::table('platforms')->insert([
            [
                'name' => 'LinkedIn',
                'type' => 'linkedin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Twitter',
                'type' => 'twitter',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Instagram',
                'type' => 'instagram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
