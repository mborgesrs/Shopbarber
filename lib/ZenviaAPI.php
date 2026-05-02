<?php
// Documentação: Classe para integração com a API da Zenvia para envio de SMS
class ZenviaAPI {
    private $apiToken;
    private $senderId;

    public function __construct($apiToken, $senderId) {
        $this->apiToken = $apiToken;
        $this->senderId = $senderId; // Pode ser uma palavra chave ou número fornecido pela Zenvia
    }

    /**
     * Documentação: Método para formatar o número de telefone para SMS (DDI + DDD + Numero)
     */
    private function formatPhone($phone) {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) == 10 || strlen($clean) == 11) {
            $clean = '55' . $clean; // Assumindo Brasil
        }
        return $clean;
    }

    /**
     * Documentação: Método principal para envio de SMS via Zenvia
     */
    public function sendSms($phone, $message) {
        $url = 'https://api.zenvia.com/v2/channels/sms/messages';
        $formattedPhone = $this->formatPhone($phone);

        // Corpo da requisição exigido pela Zenvia (V2)
        $body = json_encode([
            "from" => $this->senderId,
            "to" => $formattedPhone,
            "contents" => [
                [
                    "type" => "text",
                    "text" => $message
                ]
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        
        // Cabeçalhos (Headers) da Zenvia
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-TOKEN: ' . $this->apiToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => json_decode($response, true)
            ];
        } else {
            return [
                'success' => false,
                'error' => "HTTP Code: $httpCode. Response: $response"
            ];
        }
    }
}
