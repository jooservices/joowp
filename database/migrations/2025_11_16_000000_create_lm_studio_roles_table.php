<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lm_studio_roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('role_name')->unique();
            $table->text('role_prompt');
            $table->timestamps();
        });

        Schema::table('lm_studio_jobs', function (Blueprint $table): void {
            $table->foreignId('lm_studio_role_id')
                ->nullable()
                ->constrained('lm_studio_roles')
                ->nullOnDelete();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
        });

        DB::table('lm_studio_roles')->insert([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'role_name' => 'news-analyst-and-senior-editor-vn',
            'role_prompt' => <<<TEXT
Your tasks:
1. Read articles.
2. Extract key facts accurately.
3. Generate a concise summary.
4. Rewrite it into a fully original, professional news article.
5. Avoid copying sentences verbatim. Rewrite with a unique structure and wording.
6. Output in Vietnamese unless specified otherwise.
TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('lm_studio_jobs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lm_studio_role_id');
            $table->dropColumn([
                'prompt_tokens',
                'completion_tokens',
                'total_tokens',
            ]);
        });

        Schema::dropIfExists('lm_studio_roles');
    }
};
