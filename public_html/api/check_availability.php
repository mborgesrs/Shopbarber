<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/../../db.php';

$professional_id = $_GET['professional_id'] ?? null;
$date = $_GET['date'] ?? null;
$exclude_id = $_GET['exclude_id'] ?? null;
$company_id = $_SESSION['company_id'];

if (!$professional_id || !$date) {
    echo json_encode(['error' => 'Parâmetros insuficientes']);
    exit;
}

try {
    // We search for appointments on the given date (ignoring time)
    // We need date_time and end_time (implied or explicit)
    // Looking at quote_edit.php, end_time is updated correctly.
    // Let's ensure we fetch all non-cancelled appointments for this professional.
    
    $sql = "
        SELECT date_time, duration 
        FROM quotes 
        WHERE professional_id = ? 
        AND company_id = ? 
        AND DATE(date_time) = ? 
        AND status != 'Cancelado'
    ";
    $params = [$professional_id, $company_id, $date];
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['bookings' => $bookings]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
