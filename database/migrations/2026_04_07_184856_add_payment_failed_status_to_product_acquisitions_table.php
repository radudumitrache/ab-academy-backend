<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not enforce ENUM constraints, so no schema change is needed.
        // On MySQL/MariaDB we must extend the ENUM definition to include the new value.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE product_acquisitions MODIFY COLUMN acquisition_status ENUM('pending_payment','paid','active','completed','cancelled','expired','payment_failed') NOT NULL DEFAULT 'pending_payment'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("UPDATE product_acquisitions SET acquisition_status = 'pending_payment' WHERE acquisition_status = 'payment_failed'");
            DB::statement("ALTER TABLE product_acquisitions MODIFY COLUMN acquisition_status ENUM('pending_payment','paid','active','completed','cancelled','expired') NOT NULL DEFAULT 'pending_payment'");
        }
    }
};
