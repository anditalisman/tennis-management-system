<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Packages are no longer tied to a Program — they're classified by type
// (private/kelompok/korporat) instead, chosen at creation time rather than
// picking a program. program_id stays for existing rows/possible future use,
// it's just no longer required or shown in the create/edit form.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable(false)->change();
        });
    }
};
