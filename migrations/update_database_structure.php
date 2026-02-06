<?php
/**
 * Migration Script - Update Database Structure
 * Adds new tables: portadores, contas, tipos_pagamento
 * Updates settings and finance tables
 */

require_once __DIR__ . '/../db.php';

echo "Starting database migration...\n";

try {
    // 1. Update settings table - add CNPJ, CPF, CEP fields
    echo "Updating settings table...\n";
    $pdo->exec("ALTER TABLE settings 
        ADD COLUMN IF NOT EXISTS cnpj VARCHAR(18) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS logradouro VARCHAR(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS numero VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS complemento VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS estado VARCHAR(2) DEFAULT NULL
    ");
    echo "Settings table updated successfully.\n";

    // 2. Create portadores table
    echo "Creating portadores table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `portadores` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nome` varchar(100) NOT NULL,
        `conta` varchar(50) DEFAULT NULL,
        `agencia` varchar(20) DEFAULT NULL,
        `numero` varchar(50) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Insert sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM portadores");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `portadores` (`nome`, `conta`, `agencia`, `numero`) VALUES
            ('Caixa Principal', '12345-6', '0001', '001'),
            ('Banco do Brasil', '98765-4', '1234-5', '002')");
        echo "Sample portadores inserted.\n";
    }
    echo "Portadores table created successfully.\n";

    // 3. Create contas table
    echo "Creating contas table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `codigo` varchar(20) DEFAULT NULL,
        `descricao` varchar(200) NOT NULL,
        `tipo` enum('Analitica','Sintetica') DEFAULT 'Analitica',
        `ativo` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_codigo` (`codigo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Insert sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM contas");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `contas` (`codigo`, `descricao`, `tipo`, `ativo`) VALUES
            ('01.01.01', 'Receitas de Serviços', 'Analitica', 1),
            ('02.01.01', 'Despesas Administrativas', 'Analitica', 1),
            ('02.02.01', 'Despesas com Pessoal', 'Sintetica', 1)");
        echo "Sample contas inserted.\n";
    }
    echo "Contas table created successfully.\n";

    // 4. Create tipos_pagamento table
    echo "Creating tipos_pagamento table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tipos_pagamento` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `descricao` varchar(100) NOT NULL,
        `ativo` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Insert sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM tipos_pagamento");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `tipos_pagamento` (`descricao`, `ativo`) VALUES
            ('Dinheiro', 1),
            ('Cartão de Crédito', 1),
            ('Cartão de Débito', 1),
            ('PIX', 1),
            ('Boleto Bancário', 1),
            ('Transferência Bancária', 1)");
        echo "Sample tipos_pagamento inserted.\n";
    }
    echo "Tipos_pagamento table created successfully.\n";

    // 5. Update finance table - add new fields
    echo "Updating finance table...\n";
    $pdo->exec("ALTER TABLE finance 
        ADD COLUMN IF NOT EXISTS portador_id INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS conta_id INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS tipo_pagamento_id INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS status ENUM('Pendente','Pago','Cancelado') DEFAULT 'Pendente',
        ADD COLUMN IF NOT EXISTS data_vencimento DATE DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS data_pagamento DATE DEFAULT NULL
    ");
    
    // Add foreign keys if they don't exist
    $pdo->exec("ALTER TABLE finance 
        ADD CONSTRAINT fk_finance_portador FOREIGN KEY (portador_id) REFERENCES portadores(id) ON DELETE SET NULL,
        ADD CONSTRAINT fk_finance_conta FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE SET NULL,
        ADD CONSTRAINT fk_finance_tipo_pagamento FOREIGN KEY (tipo_pagamento_id) REFERENCES tipos_pagamento(id) ON DELETE SET NULL
    ");
    echo "Finance table updated successfully.\n";

    echo "\n✅ Migration completed successfully!\n";

} catch (PDOException $e) {
    // Ignore duplicate key errors for foreign keys
    if (strpos($e->getMessage(), 'Duplicate key') === false && 
        strpos($e->getMessage(), 'Duplicate column') === false) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        echo "⚠️  Some constraints already exist (this is OK).\n";
    }
}

echo "\nMigration script finished.\n";
