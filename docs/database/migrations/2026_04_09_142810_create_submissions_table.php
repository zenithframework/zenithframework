<?php

declare(strict_types=1);

use Zen\Database\Schema;

return new class {
    public function up(): void
    {
        Schema::create('submissions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->text('content')->nullable();
            $table->string('attachment')->nullable();
            $table->integer('score')->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('pending'); // pending, graded, resubmit
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['assignment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};