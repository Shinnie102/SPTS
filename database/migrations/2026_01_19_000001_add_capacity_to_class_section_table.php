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
        Schema::table('class_section', function (Blueprint $table) {
            $table->integer('capacity')->default(50)->after('class_code')
                ->comment('Sức chứa tối đa của lớp học');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_section', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }
};
