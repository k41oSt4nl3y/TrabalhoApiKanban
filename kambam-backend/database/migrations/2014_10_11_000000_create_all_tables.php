<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create tables without foreign key constraints first
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();
        });

        Schema::create('columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id');
            $table->string('name', 40);
            $table->integer('order');
            $table->integer('wip_limit')->default(999);
            $table->timestamps();
        });

        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id');
            $table->unsignedBigInteger('column_id');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('move_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('from_column_id')->nullable();
            $table->unsignedBigInteger('to_column_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        // Add foreign key constraints after all tables are created
        Schema::table('boards', function (Blueprint $table) {
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('columns', function (Blueprint $table) {
            $table->foreign('board_id')->references('id')->on('boards')->onDelete('cascade');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->foreign('board_id')->references('id')->on('boards')->onDelete('cascade');
            $table->foreign('column_id')->references('id')->on('columns')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('move_histories', function (Blueprint $table) {
            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            $table->foreign('from_column_id')->references('id')->on('columns')->onDelete('set null');
            $table->foreign('to_column_id')->references('id')->on('columns')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('move_histories');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('columns');
        Schema::dropIfExists('boards');
        Schema::dropIfExists('users');
    }
};
