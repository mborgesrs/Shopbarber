<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE contas");
$cols = [];
while($row = $stmt->fetch()) {
    $cols[] = $row['Field'];
}
echo implode(",", $cols);
