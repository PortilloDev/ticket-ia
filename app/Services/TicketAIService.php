<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;

class TicketAIService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('DEEPSEEK_API_KEY');
        $this->baseUrl = env('DEEPSEEK_BASE_URL');
    }

    public function enrutarTicket(string $inputUsuario): string
    {
        // PILAR 2: IDIOMA HÍBRIDO
        // System Prompt en INGLÉS para ahorrar tokens y mejorar obediencia.
        // Aunque el usuario hable español, la lógica interna es gringa.
        $systemPrompt = "You are a triage system. Classify the user input into one of these categories: 'SIMPLE_FAQ' or 'COMPLEX_ISSUE'. Return ONLY the category name.";

        $response = $this->callDeepSeek($systemPrompt, $inputUsuario, maxTokens: 10);
        
        return trim($response->body()); // Devolverá "SIMPLE_FAQ" o "COMPLEX_ISSUE"
    }
    /**
     * Recupera el manual desde Postgres.
     * TRUCO DE ARQUITECTO: 
     * Aunque esté en DB, esto es "Contexto Estático". 
     * Usamos Cache de Laravel (Redis/File) para no machacar la DB en cada request 
     * y simular que es un bloque de memoria rápido para el LLM.
     */
    private function obtenerManualDesdeDB(): string
    {
        // Cacheamos la query de DB por 60 minutos. 
        // Si el manual cambia, limpiamos caché.
        return Cache::remember('manual_completo_texto', 3600, function () {
            return KnowledgeBase::all()
                ->map(fn($item) => "TEMA: {$item->topic}\nREGLA: {$item->content}")
                ->implode("\n\n");
        });
    }

    public function resolverTicketComplejo(string $inputUsuario): array
    {
        // 1. Recuperamos el conocimiento de la empresa (Postgres)
        $contextoManual = $this->obtenerManualDesdeDB();

        // 2. Definimos System Prompt (Inglés - Idioma Híbrido)
        $systemPrompt = "You are a senior support agent. Use the provided KNOWLEDGE BASE to answer the user ticket. Return a JSON with keys: 'category', 'priority' (HIGH/LOW), and 'suggested_reply'.";

        // 3. Construimos los mensajes (Higiene de Prefijos / KV Cache Friendly)
        // ORDEN: System -> Contexto Estático (Manual DB) -> Contexto Dinámico (Usuario)
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            
            // Aquí inyectamos lo que sacamos de Postgres como un mensaje previo del usuario o system
            // Esto permite al LLM tener el contexto antes de la pregunta.
            ['role' => 'user', 'content' => "--- KNOWLEDGE BASE START ---\n" . $contextoManual . "\n--- KNOWLEDGE BASE END ---"],
            
            // La pregunta real cambia siempre, va al final.
            ['role' => 'user', 'content' => "Ticket del usuario:\n" . $inputUsuario],
        ];

        // 4. Llamada a DeepSeek con Structured Outputs (JSON)
        /** @var Response $response */
        $response = Http::withToken($this->apiKey)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => $messages,
                'response_format' => ['type' => 'json_object'], // Determinismo
                'temperature' => 0.0, // El "Contable"
            ]);

        return json_decode($response->json()['choices'][0]['message']['content'], true);
    }

    /**
     * @return Response
     */
    private function callDeepSeek(string $system, string $user, int $maxTokens = 100): Response
    {
        /** @var Response $response */
        $response = Http::withToken($this->apiKey)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.0, 
            ]);
        
        return $response;
    }

}