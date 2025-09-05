<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('two_factor_enabled')->nullable();
            $table->enum('role', ['admin', 'manager', 'viewer'])->default('viewer');
            $table->string('two_factor_secret')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        
        
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};