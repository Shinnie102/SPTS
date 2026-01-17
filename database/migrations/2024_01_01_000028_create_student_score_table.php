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
        Schema::create('student_score', function (Blueprint $table) {
            $table->id('student_score_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('component_id');
            $table->decimal('score_value', 5, 2);
            $table->dateTime('last_updated_at')->nullable();
            
            $table->foreign('enrollment_id')
                ->references('enrollment_id')
                ->on('enrollment')
                ->onDelete('cascade');
            
            $table->foreign('component_id')
                ->references('component_id')
                ->on('grading_component')
                ->onDelete('cascade');
            
            // Prevent duplicate scores for same enrollment and component
            $table->unique(['enrollment_id', 'component_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_score');
    }
};
