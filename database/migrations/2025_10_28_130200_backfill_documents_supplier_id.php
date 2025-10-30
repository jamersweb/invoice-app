<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align supplier_id with owner_id when owner_type is 'supplier' and supplier exists
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // SQLite doesn't support UPDATE ... JOIN. Do it in chunks.
            DB::table('documents')
                ->where('owner_type', 'supplier')
                ->whereNull('supplier_id')
                ->orderBy('id')
                ->chunkById(200, function ($docs) {
                    foreach ($docs as $doc) {
                        $exists = DB::table('suppliers')->where('id', $doc->owner_id)->exists();
                        if ($exists) {
                            DB::table('documents')->where('id', $doc->id)->update(['supplier_id' => $doc->owner_id]);
                        }
                    }
                });
        } else {
            DB::statement("UPDATE documents d JOIN suppliers s ON s.id = d.owner_id SET d.supplier_id = s.id WHERE d.owner_type = 'supplier' AND d.supplier_id IS NULL");
        }
    }

    public function down(): void
    {
        // No-op rollback: leave supplier_id populated
    }
};


