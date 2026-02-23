<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';

// Get request body
$body = file_get_contents('php://input');
if (!$body) {
    http_response_code(400);
    exit;
}

$data = json_decode($body, true);
$event = $data['event'] ?? '';

// For security, you should verify if it's really from Asaas (e.g., checking a secret token header)
// Asaas sends a 'asaas-access-token' header if configured in the webhook setup.
// For now, we allow the request to proceed for implementation simplicity.

if (in_array($event, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'])) {
    $payment = $data['payment'];
    $asaas_id = $payment['id'];
    $payment_date = $payment['confirmedDate'] ?? date('Y-m-d H:i:s');
    
    // Find invoice in our database
    $stmt = $pdo->prepare("SELECT * FROM saas_invoices WHERE asaas_id = ?");
    $stmt->execute([$asaas_id]);
    $invoice = $stmt->fetch();
    
    if ($invoice) {
        $pdo->prepare("UPDATE saas_invoices SET status = 'PAID', payment_date = ? WHERE id = ?")
            ->execute([$payment_date, $invoice['id']]);
            
        $company_id = $invoice['company_id'];
        
        // Update company status if it was overdue
        if ($invoice['type'] === 'SETUP') {
            $pdo->prepare("UPDATE companies SET setup_paid = 1, subscription_status = 'active' WHERE id = ?")
                ->execute([$company_id]);
        } else {
            // Update next due date? Asaas usually handles this via subscriptions, 
            // but we can update our local cache.
            $next_due = date('Y-m-d', strtotime('+30 days'));
            $pdo->prepare("UPDATE companies SET subscription_status = 'active', next_due_date = ? WHERE id = ?")
                ->execute([$next_due, $company_id]);
        }
        
        error_log("Payment confirmed for invoice " . $invoice['id']);
    }
}

if ($event === 'PAYMENT_OVERDUE') {
    $payment = $data['payment'];
    $asaas_id = $payment['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM saas_invoices WHERE asaas_id = ?");
    $stmt->execute([$asaas_id]);
    $invoice = $stmt->fetch();
    
    if ($invoice) {
        $pdo->prepare("UPDATE saas_invoices SET status = 'OVERDUE' WHERE id = ?")
            ->execute([$invoice['id']]);
            
        // Block company
        $pdo->prepare("UPDATE companies SET subscription_status = 'overdue' WHERE id = ?")
            ->execute([$invoice['company_id']]);
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
