<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Users table (if not exists)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Boards
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Columns
        Schema::create('columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->onDelete('cascade');
            $table->string('name', 40);
            $table->integer('order');
            $table->integer('wip_limit')->default(999);
            $table->timestamps();
        });

        // Cards
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->onDelete('cascade');
            $table->foreignId('column_id')->constrained()->onDelete('cascade');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Move Histories (without foreign keys initially)
        Schema::create('move_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('from_column_id')->nullable();
            $table->unsignedBigInteger('to_column_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        // Add foreign keys to move_histories
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
    }
};
