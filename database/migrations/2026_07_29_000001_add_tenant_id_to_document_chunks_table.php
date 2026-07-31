<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            // default(1) backfills existing rows to a default tenant; index it —
            // every tenant-scoped query filters on this column.
            $table->foreignId('tenant_id')->default(1)->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
