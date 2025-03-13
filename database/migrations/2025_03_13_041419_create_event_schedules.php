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
        Schema::create('events', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('event_type', length: 50);
            $table->string('title', length: 255);
            $table->text('description');
            $table->string('location', length: 255);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_exception')->default(false);
            $table->foreignId('exception_event_id')->nullable()->default(null);
            $table->boolean('exception_is_removed')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('exception_event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete()
                ->nullable()
                ->index();
            $table->index(['event_type', 'start_time', 'end_time']);
            $table->fullText('title');
        });

        Schema::create('event_recurrences', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('event_id');
            $table->string('recurrence_type', length: 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('interval');
            $table->timestamps();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('numbers', function (Blueprint $table) {
            $table->id()->primary();
            $table->integer('num', unsigned: true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbers');
        Schema::dropIfExists('event_recurrences');
        Schema::dropIfExists('events');
    }
};
