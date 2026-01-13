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
        Schema::create('room', function (Blueprint $table) {
            $table->id('room_id');
            $table->string('room_code', 50)->unique();
            $table->string('room_name', 200);
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('room_type', 50)->nullable();
            
            $table->foreign('campus_id')
                ->references('campus_id')
                ->on('campus')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};
