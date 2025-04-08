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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('event_occurence_id');
            $table->string('attendance_type', length: 20);
            $table->uuid('member_id')->nullable();
            $table->string('guest_name', length: 255)->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->foreign('event_occurence_id')
                ->references('id')
                ->on('event_occurences')
                ->cascadeOnDelete();

            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete()
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
