<?php
require_once __DIR__ . '/../db.php';
$pdo->exec('UPDATE clients SET name = UPPER(name)');
echo "Nomes atualizados com sucesso.\n";
