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
        Schema::create('class_meeting', function (Blueprint $table) {
            $table->id('meeting_id');
            $table->unsignedBigInteger('class_section_id');
            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->timestamps();
            
            $table->foreign('class_section_id')
                ->references('class_section_id')
                ->on('class_section')
                ->onDelete('cascade');
            
            $table->foreign('room_id')
                ->references('room_id')
                ->on('room')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_meeting');
    }
};
