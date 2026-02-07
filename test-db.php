<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "  Database Connection Test\n";
echo "========================================\n\n";

echo "Configuration:\n";
echo "- Connection: " . config('database.default') . "\n";
echo "- Host: " . config('database.connections.mysql.host') . "\n";
echo "- Port: " . config('database.connections.mysql.port') . "\n";
echo "- Database: " . config('database.connections.mysql.database') . "\n";
echo "- Username: " . config('database.connections.mysql.username') . "\n";
echo "\n";

echo "Testing connection...\n\n";

try {
    $pdo = DB::connection()->getPdo();
    
    echo "✅ SUCCESS! Database connection established!\n\n";
    
    echo "Connection Details:\n";
    echo "- Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "- Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    
    // Test query to get current database
    $result = DB::select('SELECT DATABASE() as current_db');
    echo "- Current Database: " . $result[0]->current_db . "\n\n";
    
    // Show tables in database
    echo "Checking tables...\n";
    $tables = DB::select('SHOW TABLES');
    
    if (count($tables) > 0) {
        echo "Found " . count($tables) . " table(s):\n";
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            echo "  - " . $tableName . "\n";
        }
    } else {
        echo "No tables found (database is empty)\n";
        echo "This is normal for a new database.\n";
    }
    
    echo "\n✅ All tests passed! You can now use this database.\n";
    
} catch (PDOException $e) {
    echo "❌ DATABASE CONNECTION FAILED!\n\n";
    echo "Error Message: " . $e->getMessage() . "\n\n";
    
    echo "Common Solutions:\n";
    echo "1. Remote MySQL Access:\n";
    echo "   - Go to cPanel → Remote MySQL\n";
    echo "   - Add your public IP address\n";
    echo "   - Find your IP at: https://whatismyipaddress.com/\n\n";
    
    echo "2. Check Credentials:\n";
    echo "   - Verify database name in cPanel (includes prefix)\n";
    echo "   - Verify username in cPanel (includes prefix)\n";
    echo "   - Verify password is correct\n\n";
    
    echo "3. Try Server IP:\n";
    echo "   - Instead of domain name, try server IP address\n";
    echo "   - Update DB_HOST in .env file\n\n";
    
    echo "4. Check Firewall:\n";
    echo "   - Your firewall might be blocking port 3306\n";
    echo "   - Try disabling firewall temporarily to test\n\n";
    
} catch (Exception $e) {
    echo "❌ UNEXPECTED ERROR!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n========================================\n";
