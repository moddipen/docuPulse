<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: table is 'answer_cache' (singular), not the default 'answer_caches'.
        Schema::table('answer_cache', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->index();
        });
    }

    public function down(): void
    {
        Schema::table('answer_cache', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
