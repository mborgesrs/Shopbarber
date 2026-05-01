<?php
// Documentação: Este script adiciona as colunas necessárias na tabela 'settings' para armazenar a configuração do Evolution API
require_once __DIR__ . '/../db.php';

try {
    // Array com as novas colunas a serem adicionadas: Nome => Tipo SQL
    // Documentação: evolution_api_url armazena o endereço do servidor do Evolution (ex: https://api.meuservidor.com)
    // Documentação: evolution_instance armazena o nome da instância conectada (ex: barber123)
    // Documentação: evolution_api_key armazena o token/apikey de segurança do Evolution
    // Documentação: evolution_active é um booleano (0 ou 1) que indica se os disparos automáticos estão ligados
    $cols = [
        'evolution_api_url' => 'VARCHAR(255) DEFAULT NULL',
        'evolution_instance' => 'VARCHAR(100) DEFAULT NULL',
        'evolution_api_key' => 'VARCHAR(255) DEFAULT NULL',
        'evolution_active' => 'TINYINT(1) DEFAULT 0'
    ];

    // Percorre cada coluna para verificar se já existe na tabela 'settings' antes de tentar criar
    foreach ($cols as $col => $def) {
        $check = $pdo->prepare("SHOW COLUMNS FROM settings LIKE ?");
        $check->execute([$col]);
        
        // Documentação: Se a coluna não existir, executa o comando ALTER TABLE para adicioná-la
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN $col $def");
            echo "Coluna $col adicionada com sucesso.\n";
        } else {
            echo "Coluna $col já existe.\n";
        }
    }
    
    // Documentação: Mensagem de sucesso ao final do script
    echo "Migração Evolution API finalizada com sucesso!\n";
} catch (Exception $e) {
    // Documentação: Captura e exibe qualquer erro que possa ocorrer durante a alteração do banco
    die("Erro na migração: " . $e->getMessage() . "\n");
}
