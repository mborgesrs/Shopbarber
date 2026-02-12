<?php

/**
 * Formata um número de telefone para o padrão brasileiro:
 * 10 dígitos: (XX) XXXX-XXXX
 * 11 dígitos: (XX) 9XXXX-XXXX
 */
function formatPhone($phone) {
    if (empty($phone)) return '-';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $length = strlen($phone);
    
    if ($length == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    } elseif ($length == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    
    return $phone;
}
