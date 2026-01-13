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
        Schema::create('attendance', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_meeting_id');
            $table->unsignedBigInteger('class_section_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('cascade');
            
            $table->foreign('class_meeting_id')
                ->references('meeting_id')
                ->on('class_meeting')
                ->onDelete('cascade');
            
            $table->foreign('class_section_id')
                ->references('class_section_id')
                ->on('class_section')
                ->onDelete('cascade');
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('attendance_status')
                ->onDelete('restrict');
            
            $table->foreign('recorded_by')
                ->references('user_id')
                ->on('user')
                ->onDelete('set null');
            
            // Prevent duplicate attendance records
            $table->unique(['user_id', 'class_meeting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
