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
        Schema::create('class_section', function (Blueprint $table) {
            $table->id('class_section_id');
            $table->string('class_code', 50)->unique();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->integer('max_students')->default(0);
            $table->integer('current_students')->default(0);
            $table->unsignedBigInteger('status_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('course')
                ->onDelete('cascade');
            
            $table->foreign('semester_id')
                ->references('semester_id')
                ->on('semester')
                ->onDelete('cascade');
            
            $table->foreign('instructor_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('set null');
            
            $table->foreign('room_id')
                ->references('room_id')
                ->on('room')
                ->onDelete('set null');
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('class_section_status')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_section');
    }
};
