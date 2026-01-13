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
        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('code_user', 50)->unique();
            $table->string('username', 100)->unique();
            $table->string('password_hash');
            $table->unsignedBigInteger('role_id');
            $table->string('full_name', 200);
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->date('birth')->nullable();
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('major', 200)->nullable();
            $table->date('orientation_day')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index('email');
            $table->index('username');
            $table->index('code_user');
            $table->index('role_id');
            $table->index('status_id');
            
            $table->foreign('role_id')
                ->references('role_id')
                ->on('role')
                ->onDelete('restrict');
            
            $table->foreign('gender_id')
                ->references('gender_id')
                ->on('gender_lookup')
                ->onDelete('set null');
            
            $table->foreign('status_id')
                ->references('status_id')
                ->on('user_status')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
