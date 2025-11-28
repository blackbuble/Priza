<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->string('type')->default('text');
            $table->timestamps();
        });

        // Insert default settings
        DB::table('lottery_settings')->insert([
            [
                'key' => 'lottery_active',
                'value' => '0',
                'description' => 'Status aktif undian',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_winners',
                'value' => '3',
                'description' => 'Jumlah maksimal pemenang',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'announcement',
                'value' => 'Selamat kepada para pemenang undian!',
                'description' => 'Pengumuman untuk pemenang',
                'type' => 'textarea',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_draw',
                'value' => '0',
                'description' => 'Undian otomatis pada waktu tertentu',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'draw_time',
                'value' => '18:00',
                'description' => 'Waktu undian otomatis (HH:MM)',
                'type' => 'time',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'show_winners',
                'value' => '1',
                'description' => 'Tampilkan pemenang secara publik',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_settings');
    }
};