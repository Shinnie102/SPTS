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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_section_id');
            $table->date('enrollment_date');
            $table->unsignedBigInteger('status_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('cascade');
            
            $table->foreign('class_section_id')
                ->references('class_section_id')
                ->on('class_section')
                ->onDelete('cascade');
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('enrollment_status')
                ->onDelete('restrict');
            
            // Prevent duplicate enrollment
            $table->unique(['user_id', 'class_section_id']);
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
