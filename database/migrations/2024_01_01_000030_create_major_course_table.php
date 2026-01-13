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
        Schema::create('major_course', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('major_id');
            $table->unsignedBigInteger('course_id');
            $table->boolean('is_required')->default(true);
            $table->integer('semester_order')->nullable();
            $table->timestamps();
            
            $table->foreign('major_id')
                ->references('major_id')
                ->on('major')
                ->onDelete('cascade');
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('course')
                ->onDelete('cascade');
            
            // Prevent duplicate relationships
            $table->unique(['major_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('major_course');
    }
};
