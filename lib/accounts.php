<?php

/**
 * Recalculates the total balance for each account in the Chart of Accounts (contas).
 * 
 * @param int $company_id The ID of the company.
 * @param PDO $pdo The PDO connection instance.
 */
function recalculateAccountTotals($company_id, $pdo) {
    try {
        // 1. Reset all totals for the company
        $stmt = $pdo->prepare("UPDATE contas SET total = 0 WHERE company_id = ?");
        $stmt->execute([$company_id]);

        // 2. Sum values for Analytical accounts from the finance table
        // We use the same business logic as finance.php for signs:
        // Entrada/Receber/cRecebido are positive.
        // Saida/Pagar/dPago/etc are negative.
        $sqlAnaliticas = "
            UPDATE contas c 
            SET c.total = (
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN f.type IN ('Entrada', 'Receber', 'cRecebido') THEN f.value 
                        ELSE -f.value 
                    END
                ), 0)
                FROM finance f
                WHERE f.conta_id = c.id AND f.company_id = ?
            )
            WHERE c.tipo = 'Analitica' AND c.company_id = ?
        ";
        $stmt = $pdo->prepare($sqlAnaliticas);
        $stmt->execute([$company_id, $company_id]);

        // 3. Roll up totals for Synthetic accounts
        // We fetch all accounts ordered by the length of the code DESC to process children before parents
        $stmt = $pdo->prepare("SELECT id, codigo, tipo FROM contas WHERE company_id = ? ORDER BY LENGTH(codigo) DESC, codigo DESC");
        $stmt->execute([$company_id]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($accounts as $acc) {
            if ($acc['tipo'] === 'Sintetica') {
                // A synthetic account's total is the sum of totals of its direct children or all descendants.
                // Simpler: sum of all Analitica accounts that start with this code.
                // Example: 01.01 total = SUM of analyticals starting with 01.01.
                $sqlSintetica = "
                    UPDATE contas 
                    SET total = (
                        SELECT COALESCE(SUM(total), 0) 
                        FROM (SELECT total FROM contas WHERE company_id = ? AND tipo = 'Analitica' AND codigo LIKE ?) AS sub
                    )
                    WHERE id = ?
                ";
                $childPattern = $acc['codigo'] . '.%';
                $stmtUpd = $pdo->prepare($sqlSintetica);
                $stmtUpd->execute([$company_id, $childPattern, $acc['id']]);
            }
        }

    } catch (PDOException $e) {
        // Log error or handle as needed
        error_log("Error recalculating account totals: " . $e->getMessage());
    }
}
