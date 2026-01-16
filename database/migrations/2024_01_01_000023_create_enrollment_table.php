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
        Schema::create('enrollment', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_section_id');
            $table->unsignedBigInteger('enrollment_status_id');
            $table->dateTime('enrolled_at')->nullable();
            $table->timestamps();
            
            $table->foreign('student_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('cascade');
            
            $table->foreign('class_section_id')
                ->references('class_section_id')
                ->on('class_section')
                ->onDelete('cascade');
            
            $table->foreign('enrollment_status_id')
                ->references('status_id')
                ->on('enrollment_status')
                ->onDelete('restrict');
            
            // Prevent duplicate enrollment
            $table->unique(['student_id', 'class_section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};
