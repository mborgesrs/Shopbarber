<?php
require_once __DIR__ . '/../db.php';

function checkBillingStatus($company_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    
    if (!$company) return 'inactive';

    // 1. Check status
    if ($company['subscription_status'] === 'inactive') return 'blocked';
    
    // 2. Check Overdue (simple check against database)
    if ($company['subscription_status'] === 'overdue') return 'blocked';

    // 3. Setup Check
    if ($company['setup_paid'] == 0) {
        // Only block setup if it's been more than X days?
        // For now, let's assume it MUST be paid to use.
        // return 'blocked_setup';
    }

    // 4. Trial expired check
    if ($company['subscription_status'] === 'trialing') {
        if ($company['trial_ends_at'] && strtotime($company['trial_ends_at']) < time()) {
            // Update status to overdue
            $pdo->prepare("UPDATE companies SET subscription_status = 'overdue' WHERE id = ?")->execute([$company_id]);
            return 'blocked';
        }
    }

    return 'active';
}

function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
}
