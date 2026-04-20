<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->nullable();
            $table->string('short_code', 50)->nullable()->index();
            $table->text('caption')->nullable();
            $table->text('hashtags')->nullable();
            $table->text('mentions')->nullable();
            $table->string('url')->nullable();
            $table->integer('comments_count')->nullable();
            $table->text('first_comment')->nullable();
            $table->text('latest_comments')->nullable();
            $table->integer('dimensions_height')->nullable();
            $table->integer('dimensions_width')->nullable();
            $table->text('display_url')->nullable();
            $table->text('images')->nullable();
            $table->text('alt')->nullable();
            $table->integer('likes_count')->nullable();
            $table->dateTime('timestamp')->nullable()->index();
            $table->text('child_posts')->nullable();
            $table->string('owner_full_name', 150)->nullable();
            $table->string('owner_username', 100)->nullable();
            $table->bigInteger('owner_id')->nullable();
            $table->tinyInteger('is_comments_disabled')->nullable();
            $table->string('input_url')->nullable();
            $table->tinyInteger('is_sponsored')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
