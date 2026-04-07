<?php

declare(strict_types=1);

use Zen\Database\Schema;

return new class {
    public function up(): void
    {
        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
