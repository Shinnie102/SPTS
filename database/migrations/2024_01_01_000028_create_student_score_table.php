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
            $table->id('score_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('grading_component_id');
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('enrollment_id')
                ->references('enrollment_id')
                ->on('enrollment')
                ->onDelete('cascade');
            
            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('cascade');
            
            $table->foreign('grading_component_id')
                ->references('component_id')
                ->on('grading_component')
                ->onDelete('cascade');
            
            $table->foreign('recorded_by')
                ->references('user_id')
                ->on('user')
                ->onDelete('set null');
            
            // Prevent duplicate scores for same enrollment and component
            $table->unique(['enrollment_id', 'grading_component_id']);
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
