<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->json('title');
            $table->json('slug');
            $table->json('excerpt')->nullable();
            $table->json('content')->nullable();

            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('seo_keywords')->nullable();

            $table->string('og_image')->nullable();
            $table->unsignedSmallInteger('reading_time')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        DB::statement("
            ALTER TABLE posts
            ADD COLUMN slug_de VARCHAR(200) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(slug, '$.de'))) VIRTUAL,
            ADD COLUMN slug_en VARCHAR(200) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en'))) VIRTUAL
        ");

        DB::statement('ALTER TABLE posts ADD UNIQUE INDEX uniq_slug_de (slug_de, deleted_at)');
        DB::statement('ALTER TABLE posts ADD UNIQUE INDEX uniq_slug_en (slug_en, deleted_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
