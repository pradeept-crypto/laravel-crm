<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultUserId = User::first()?->id ?? 1;

        DB::table('leads')
            ->whereNull('user_id')
            ->update(['user_id' => $defaultUserId]);

        DB::table('persons')
            ->whereNull('user_id')
            ->update(['user_id' => $defaultUserId]);

        $defaultPipeline = DB::table('lead_pipelines')->where('is_default', 1)->first()
            ?: DB::table('lead_pipelines')->first();

        if ($defaultPipeline) {
            $defaultStage = DB::table('lead_pipeline_stages')
                ->where('lead_pipeline_id', $defaultPipeline->id)
                ->orderBy('sort_order', 'asc')
                ->first();

            if ($defaultStage) {
                DB::table('leads')
                    ->whereNull('lead_pipeline_id')
                    ->update([
                        'lead_pipeline_id' => $defaultPipeline->id,
                        'lead_pipeline_stage_id' => $defaultStage->id,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
