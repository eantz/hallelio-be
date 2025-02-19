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
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', length: 255);
            $table->string('last_name', length: 255);
            $table->string('birth_place', length: 255);
            $table->date('birth_date')->nullable();
            $table->string('phone_number', length: 20);
            $table->text('address');
            $table->string('personal_id_number', length: 255)->nullable()->unique();
            $table->string('picture', length: 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
