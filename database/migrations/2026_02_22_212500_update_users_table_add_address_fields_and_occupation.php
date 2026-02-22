<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // First, store the current address values
            $users = DB::table('users')->whereNotNull('address')->get(['id', 'address']);
            
            // Add new address fields
            $table->string('street')->nullable()->after('address');
            $table->string('house_number')->nullable()->after('street');
            $table->string('city')->nullable()->after('house_number');
            $table->string('county')->nullable()->after('city');
            $table->string('country')->nullable()->after('county');
            $table->string('occupation')->nullable()->after('country');
        });
        
        // We'll keep the original address field for backward compatibility
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'street',
                'house_number',
                'city',
                'county',
                'country',
                'occupation'
            ]);
        });
    }
};
