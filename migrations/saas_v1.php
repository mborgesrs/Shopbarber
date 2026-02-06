<?php
require_once __DIR__ . '/../db.php';

try {
    $pdo->beginTransaction();

    // 1. Create companies table
    echo "Creating companies table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        fantasy_name VARCHAR(255),
        document VARCHAR(20),
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Create default company if not exists
    echo "Creating default company...\n";
    $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
    $defaultCompanyId = $stmt->fetchColumn();

    if (!$defaultCompanyId) {
        $pdo->exec("INSERT INTO companies (name, fantasy_name, status) VALUES ('Minha Barbearia', 'ShopBarber Default', 'active')");
        $defaultCompanyId = $pdo->lastInsertId();
    }
    echo "Default Company ID: $defaultCompanyId\n";

    // 3. Helper function to add company_id
    function addCompanyColumn($pdo, $table, $defaultId) {
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'company_id'");
        if (!$stmt->fetch()) {
            echo "Adding company_id to $table...\n";
            $pdo->exec("ALTER TABLE $table ADD COLUMN company_id INT NOT NULL DEFAULT '$defaultId'");
            // Add index for performance
            $pdo->exec("CREATE INDEX idx_{$table}_company ON $table(company_id)");
        } else {
            echo "Column company_id already exists in $table.\n";
        }
    }

    // 4. Apply to tables
    $tables = ['users', 'clients', 'professionals', 'products', 'quotes', 'finance', 'settings'];
    foreach ($tables as $table) {
        addCompanyColumn($pdo, $table, $defaultCompanyId);
    }
    
    // Note: quote_items usually don't need company_id if they are accessed via quotes which has it, 
    // but for analytics it might be useful. For strict tenancy, linking via quote is enough usually.
    // However, keeping it normalized. Let's just stick to main entities for now.

    $pdo->commit();
    echo "Migration SAAS V1 completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
