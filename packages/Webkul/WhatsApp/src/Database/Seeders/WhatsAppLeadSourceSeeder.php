<?php

namespace Webkul\WhatsApp\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsAppLeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $exists = DB::table('lead_sources')->where('name', 'WhatsApp')->exists();

        if (! $exists) {
            DB::table('lead_sources')->insert([
                'name' => 'WhatsApp',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
