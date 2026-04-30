<?php
/**
 * PRODUCTION MIGRATION SCRIPT
 * For Expert Gestion Superadmin Dashboard
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting Production Migration...\n";
echo "---------------------------------\n";

try {
    // 1. Update clients table
    echo "Checking 'clients' table structure...\n";
    
    // Ensure id_client is UNIQUE (required for Foreign Keys)
    $stmt = $pdo->query("SHOW KEYS FROM clients WHERE Column_name = 'id_client'");
    $hasIndex = false;
    while($row = $stmt->fetch()) {
        if ($row['Non_unique'] == 0) $hasIndex = true;
    }
    
    if (!$hasIndex) {
        echo "Adding UNIQUE index to 'id_client'...\n";
        $pdo->exec("ALTER TABLE clients ADD UNIQUE (id_client)");
        echo "[SUCCESS] 'id_client' is now indexed.\n";
    }

    $cols = $pdo->query("SHOW COLUMNS FROM clients")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('notes', $cols)) {
        $pdo->exec("ALTER TABLE clients ADD COLUMN notes TEXT NULL");
        echo "[SUCCESS] Added 'notes' column.\n";
    }

    if (!in_array('is_locked', $cols)) {
        $pdo->exec("ALTER TABLE clients ADD COLUMN is_locked TINYINT(1) DEFAULT 0");
        echo "[SUCCESS] Added 'is_locked' column.\n";
    }

    // 2. Subscriptions table
    echo "Creating 'subscriptions' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(20) NOT NULL,
        month VARCHAR(7) NOT NULL,
        is_paid TINYINT(1) DEFAULT 0,
        connections_count INT DEFAULT 0,
        UNIQUE KEY client_month (client_id, month),
        FOREIGN KEY (client_id) REFERENCES clients(id_client) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "[SUCCESS] 'subscriptions' table ready.\n";

    // 3. Daily Access table
    echo "Creating 'daily_access' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_access (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(20) NOT NULL,
        access_date DATE NOT NULL,
        attempt_count INT DEFAULT 0,
        UNIQUE KEY client_date (client_id, access_date),
        FOREIGN KEY (client_id) REFERENCES clients(id_client) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "[SUCCESS] 'daily_access' table ready.\n";

    // 4. Payment Records table
    echo "Creating 'payment_records' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(20) NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        amount DECIMAL(15, 3) NOT NULL,
        period_type ENUM('month', 'year') NOT NULL,
        period_value VARCHAR(20) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        admin_notes TEXT,
        FOREIGN KEY (client_id) REFERENCES clients(id_client) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "[SUCCESS] 'payment_records' table ready.\n";

    echo "---------------------------------\n";
    echo "Migration completed successfully!\n";
    echo "You can now use superadmin.php.\n";

} catch (Exception $e) {
    echo "---------------------------------\n";
    echo "CRITICAL ERROR DURING MIGRATION:\n";
    echo $e->getMessage() . "\n";
}
