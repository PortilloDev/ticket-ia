<?php

namespace App\Http\Controllers;

use App\Services\TicketAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function handle(Request $request, TicketAIService $ai): JsonResponse
    {
        // Validación básica
        $request->validate([
            'ticket' => 'required|string|min:5|max:1000'
        ]);

        $ticket = $request->input('ticket');

        // PASO 1: ROUTING (El Portero)
        // Detectamos intención de forma barata
        $tipo = $ai->enrutarTicket($ticket);

        // Si el router no devuelve un valor válido, lo tratamos como off-topic
        if ($tipo === 'OFF_TOPIC') {
            return response()->json([
                'status' => 'rejected',
                'message' => 'Soy un asistente especializado en soporte de TicketAI. No puedo ayudarte con consultas fuera de este tema.'
            ], 400);
        }
        // Si el router falla o no devuelve lo esperado, por seguridad lo tratamos como complejo
        if (!in_array($tipo, ['SIMPLE_FAQ', 'COMPLEX_ISSUE'])) {
            $tipo = 'COMPLEX_ISSUE';
        }

        if ($tipo === 'SIMPLE_FAQ') {
            return response()->json([
                'status' => 'success',
                'router_decision' => $tipo,
                'data' => [
                    'message' => '¡Hola! Veo que es una consulta rápida. ¿Has probado a mirar en nuestro FAQ o resetear tu contraseña?'
                ]
            ]);
        }

        // PASO 2: SOLVER (El Experto con RAG)
        $analisis = $ai->resolverTicketComplejo($ticket);

        return response()->json([
            'status' => 'success',
            'router_decision' => $tipo,
            'data' => $analisis
        ]);
    }
}
