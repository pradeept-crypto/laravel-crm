<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lead_id')->unsigned()->nullable();
            $table->integer('person_id')->unsigned()->nullable();
            $table->string('wa_message_id')->nullable()->index();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('from_number');
            $table->string('to_number');
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('status')->default('queued');
            $table->json('raw_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete();

            $table->index(['from_number', 'to_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
