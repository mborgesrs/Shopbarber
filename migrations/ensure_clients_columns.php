<?php
/**
 * Verifica e adiciona colunas faltantes na tabela `clients`.
 * Execute via CLI: php migrations/ensure_clients_columns.php
 */
require_once __DIR__ . '/../db.php';

$expected = [
  'company' => "ADD COLUMN company VARCHAR(150) DEFAULT NULL",
  'notes' => "ADD COLUMN notes TEXT",
  'date_nascto' => "ADD COLUMN date_nascto DATE DEFAULT NULL",
  'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

function columnExists($pdo, $db, $table, $column){
  $sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$db, $table, $column]);
  return (bool)$stmt->fetchColumn();
}

$table = 'clients';
$db = isset($db_name) ? $db_name : null;
if(!$db){
  echo "ERRO: variável \$db_name não encontrada em config.php\n"; exit(1);
}

foreach($expected as $col => $ddl){
  if(!columnExists($pdo, $db, $table, $col)){
    echo "Adicionando coluna $col...\n";
    try{
      $pdo->exec("ALTER TABLE $table $ddl");
      echo "  OK\n";
    }catch(PDOException $e){
      echo "  ERRO: " . $e->getMessage() . "\n";
    }
  }else{
    echo "Coluna $col já existe.\n";
  }
}

echo "Migração concluída.\n";
