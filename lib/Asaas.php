<?php

class Asaas {
    private $apiKey;
    private $apiUrl;

    public function __construct($apiKey = null, $environment = null) {
        if (!$apiKey || !$environment) {
            // Try to fetch from database if session is active
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['company_id'])) {
                global $pdo;
                if (isset($pdo)) {
                    $stmt = $pdo->prepare("SELECT asaas_api_key, asaas_environment FROM settings WHERE company_id = ? LIMIT 1");
                    $stmt->execute([$_SESSION['company_id']]);
                    $settings = $stmt->fetch();
                    if ($settings) {
                        if (!$apiKey) $apiKey = $settings['asaas_api_key'];
                        if (!$environment) $environment = $settings['asaas_environment'];
                    }
                }
            }
        }

        $this->apiKey = $apiKey ?: (defined('ASAAS_API_KEY') ? ASAAS_API_KEY : '');
        $env = $environment ?: (defined('ASAAS_ENVIRONMENT') ? ASAAS_ENVIRONMENT : 'sandbox');
        $this->apiUrl = $env === 'sandbox' ? 'https://sandbox.asaas.com/api/v3' : 'https://www.asaas.com/api/v3';
    }

    private function request($method, $endpoint, $data = null) {
        $ch = curl_init();
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: ShopBarber/1.0'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => json_decode($response, true),
            'raw' => $response,
            'curl_error' => $error,
            'curl_errno' => $errno
        ];
    }

    public function createCustomer($data) {
        return $this->request('POST', '/customers', $data);
    }

    public function createPayment($data) {
        return $this->request('POST', '/payments', $data);
    }

    public function createSubscription($data) {
        return $this->request('POST', '/subscriptions', $data);
    }

    public function getPayment($id) {
        return $this->request('GET', '/payments/' . $id);
    }

    public function getSubscription($id) {
        return $this->request('GET', '/subscriptions/' . $id);
    }

    public function getAccountInfo() {
        return $this->request('GET', '/myAccount');
    }

    public function getWebhook() {
        return $this->request('GET', '/webhook');
    }

    public function createWebhook($url, $email, $apiVersion = 3) {
        $data = [
            'url' => $url,
            'email' => $email,
            'apiVersion' => $apiVersion,
            'enabled' => true,
            'interrupted' => false,
            'sendType' => 'SEQUENTIALLY',
            'events' => [
                'PAYMENT_CONFIRMED',
                'PAYMENT_RECEIVED',
                'PAYMENT_OVERDUE',
                'PAYMENT_DELETED',
                'SUBSCRIPTION_DELETED'
            ]
        ];
        return $this->request('POST', '/webhook', $data);
    }
}
