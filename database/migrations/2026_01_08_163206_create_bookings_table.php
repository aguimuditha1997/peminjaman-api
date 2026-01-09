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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('organization');
            $table->string('email');
            $table->enum('status_dpt',['pending','approve','rejected'])->default('pending');
            $table->enum('status_sdm',['pending','approve','rejected'])->default('pending');
            $table->enum('type_week',['weekday','weekend'])->default('weekday');
            $table->string('no_whatsapp');
            $table->foreignId('room_id')->constrained();
            $table->timestamp('start-time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->longText('note');
            $table->string('purpose');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
