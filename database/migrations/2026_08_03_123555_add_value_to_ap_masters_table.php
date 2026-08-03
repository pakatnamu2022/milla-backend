<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // DB::table bypasses el mutador uppercase de Eloquent, así las URLs se guardan tal cual
        DB::table('ap_masters')->insert([
            [
                'code'        => 'LETTER_URL',
                'description' => 'https://pendiente-configurar.com/carta-bienvenida.pdf',
                'type'        => 'VEHICLE_WELCOME_CONFIG',
                'status'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'code'        => 'VIDEO_URL',
                'description' => 'https://pendiente-configurar.com/video-bienvenida.mp4',
                'type'        => 'VEHICLE_WELCOME_CONFIG',
                'status'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('ap_masters')
            ->where('type', 'VEHICLE_WELCOME_CONFIG')
            ->whereIn('code', ['LETTER_URL', 'VIDEO_URL'])
            ->delete();
    }
};
