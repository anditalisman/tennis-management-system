<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// This deployment operates a single location and no longer models branches
// as a concept staff interact with — branch_id becomes optional everywhere
// it was previously a mandatory foreign key, so participants/courts/classes/
// inventory items/invoices can be created without any Branch row existing.
return new class extends Migration
{
    private const TABLES = ['participants', 'courts', 'classes', 'inventory_items', 'invoices'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('branch_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('branch_id')->nullable(false)->change();
            });
        }
    }
};
