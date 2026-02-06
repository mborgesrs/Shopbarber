<?php
require_once __DIR__ . '/../db.php';

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Standard ALTER TABLE doesn't support IF NOT EXISTS in all MySQL versions
    echo "Updating companies table with billing fields...\n";
    
    $cols = [
        'asaas_customer_id' => "VARCHAR(50)",
        'plan_id' => "VARCHAR(50) DEFAULT 'monthly_79'",
        'billing_cycle' => "ENUM('monthly', 'yearly') DEFAULT 'monthly'",
        'setup_paid' => "TINYINT(1) DEFAULT 0",
        'next_due_date' => "DATE",
        'subscription_status' => "ENUM('active', 'inactive', 'overdue', 'trialing') DEFAULT 'trialing'",
        'trial_ends_at' => "DATETIME",
        'asaas_subscription_id' => "VARCHAR(50)"
    ];

    foreach ($cols as $col => $type) {
        if (!columnExists($pdo, 'companies', $col)) {
            echo "Adding $col to companies...\n";
            $pdo->exec("ALTER TABLE companies ADD COLUMN $col $type");
        }
    }

    echo "Creating saas_invoices table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS saas_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        asaas_id VARCHAR(50) UNIQUE,
        amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'PENDING',
        type ENUM('SETUP', 'MONTHLY', 'YEARLY') NOT NULL,
        due_date DATE,
        payment_date DATETIME,
        invoice_url TEXT,
        payment_link TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )");

    echo "Adding role to users table...\n";
    if (!columnExists($pdo, 'users', 'role')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'user', 'super_admin') DEFAULT 'admin'");
    }

    echo "Migration SAAS_BILLING completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
