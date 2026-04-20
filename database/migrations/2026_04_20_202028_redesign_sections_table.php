<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sections');

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->boolean('enabled')->default(true);
            $table->integer('order')->default(0);
            $table->json('content')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'key']);
            $table->index(['page_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['hero', 'intro', 'featured_dishes', 'gallery_teaser', 'story', 'contact_cta']);
            $table->integer('order')->default(0);
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('body')->nullable();
            $table->json('cta_label')->nullable();
            $table->json('cta_link')->nullable();
            $table->string('image_path')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->index(['page_id', 'order']);
        });
    }
};
