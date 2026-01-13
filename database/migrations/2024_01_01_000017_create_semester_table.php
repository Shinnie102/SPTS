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
        Schema::create('semester', function (Blueprint $table) {
            $table->id('semester_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('semester_code', 20)->unique();
            $table->string('semester_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('status_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('academic_year_id')
                ->references('academic_year_id')
                ->on('academic_year')
                ->onDelete('cascade');
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('semester_status')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semester');
    }
};
