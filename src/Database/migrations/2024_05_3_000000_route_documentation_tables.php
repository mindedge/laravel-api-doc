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

        Schema::create('api_docs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->default('');
            $table->text('description')->default('');
            $table->string('version');
            $table->json('servers')->default('[]');
            $table->json('tags')->default('[]');
            $table->json('components')->default('[]');
            $table->json('security_schemes')->default('[]');
            $table->boolean('deprecated')->default(false);
            $table->boolean('enabled')->default(false);
            $table->json('metadata')->default('[]');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['version']);
            $table->index(['version']);
        });

        Schema::create('api_doc_routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('api_doc_id');
            $table->string('name')->default('');
            $table->text('description')->default('');
            $table->string('path');
            $table->string('controller');
            $table->string('action');
            $table->string('method');
            $table->json('middleware')->default('[]');
            $table->json('tags')->default('[]');
            $table->json('parameters')->default('[]');
            $table->json('responses')->default('[]');
            $table->boolean('deprecated')->default(false);
            $table->boolean('enabled')->default(false);
            $table->json('metadata')->default('[]');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['path', 'method']);
            $table->index(['api_doc_id', 'path', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_docs');
        Schema::dropIfExists('api_doc_routes');
    }
};
