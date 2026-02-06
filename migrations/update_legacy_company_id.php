<?php
require_once __DIR__ . '/../db.php';
try {
    $pdo->exec("UPDATE portadores SET company_id = 1");
    $pdo->exec("UPDATE tipos_pagamento SET company_id = 1");
    echo "Sucesso: company_id alterado para 1 nas tabelas portadores e tipos_pagamento.";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
