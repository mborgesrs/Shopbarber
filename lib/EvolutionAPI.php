<?php
// Documentação: Esta classe gerencia a comunicação com a Evolution API para envio de mensagens de WhatsApp
class EvolutionAPI {
    // Variável para armazenar a URL base da API
    private $apiUrl;
    // Variável para armazenar o nome da instância conectada no Evolution
    private $instance;
    // Variável para armazenar a chave de autenticação (Global API Key ou Instance API Key)
    private $apiKey;

    // Documentação: O construtor recebe os dados de configuração e inicializa a classe
    public function __construct($apiUrl, $instance, $apiKey) {
        // Documentação: Remove a barra (/) no final da URL, caso exista, para padronizar
        $this->apiUrl = rtrim($apiUrl, '/');
        // Documentação: Atribui a instância
        $this->instance = $instance;
        // Documentação: Atribui a chave da API
        $this->apiKey = $apiKey;
    }

    /**
     * Documentação: Método para formatar o número de telefone
     * A Evolution API exige o formato com DDI e DDD (ex: 5511999999999)
     */
    private function formatPhone($phone) {
        // Documentação: Remove todos os caracteres que não sejam números (ex: parênteses, traços, espaços)
        $clean = preg_replace('/\D/', '', $phone);
        
        // Documentação: Se o número limpo tiver 10 ou 11 dígitos, significa que está sem o código do país (Brasil = 55)
        if (strlen($clean) == 10 || strlen($clean) == 11) {
            // Documentação: Adiciona o '55' no início
            $clean = '55' . $clean;
        }
        
        // Documentação: Retorna o número pronto para a API
        return $clean;
    }

    /**
     * Documentação: Método principal para envio de mensagem de texto simples
     * Recebe o número de destino e o conteúdo da mensagem
     */
    public function sendText($phone, $message) {
        // Documentação: Monta a URL completa do endpoint da Evolution API para envio de texto
        $url = $this->apiUrl . '/message/sendText/' . $this->instance;

        // Documentação: Formata o telefone usando o método criado acima
        $formattedPhone = $this->formatPhone($phone);

        // Documentação: Prepara o corpo da requisição no formato JSON esperado pela Evolution API
        $body = json_encode([
            "number" => $formattedPhone, // Telefone formatado
            "text" => $message,          // Conteúdo da mensagem
            "delay" => 1200              // Delay de digitação (1,2 segundos) para parecer mais humano
        ]);

        // Documentação: Inicializa o CURL para fazer a requisição HTTP POST
        $ch = curl_init($url);
        
        // Documentação: Configurações do CURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retornar a resposta como string
        curl_setopt($ch, CURLOPT_POST, true);           // Definir o método como POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);    // Anexar o corpo (JSON) na requisição
        
        // Documentação: Define os cabeçalhos (headers) da requisição
        // É essencial enviar o 'Content-Type: application/json' e o 'apikey' para autenticação
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $this->apiKey
        ]);

        // Documentação: Executa a requisição e armazena a resposta do servidor
        $response = curl_exec($ch);
        // Documentação: Captura o código HTTP de retorno (ex: 200, 201, 400, etc)
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Documentação: Fecha a conexão CURL para liberar memória
        curl_close($ch);

        // Documentação: Retorna um array com o status da operação
        // Consideramos sucesso se o código HTTP estiver entre 200 e 299
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
