<?php

namespace App\Http\Controllers;

use App\Services\TicketAIService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function handle(Request $request, TicketAIService $ai)
    {
        $ticket = $request->input('ticket'); // Ej: "No me va el login"

        // PASO 1: ROUTING (El Portero)
        // Gastamos muy poco para saber de qué va el tema.
        $tipo = $ai->enrutarTicket($ticket);

        if ($tipo === 'SIMPLE_FAQ') {
            // Caso barato: No llamamos al modelo grande ni cargamos el manual.
            // Podríamos devolver un texto predefinido o usar un modelo muy pequeño.
            return response()->json([
                'source' => 'Router',
                'message' => 'Parece una duda rápida. ¿Has mirado nuestra página de ayuda?'
            ]);
        }

        // PASO 2: SOLVER (El Experto)
        // Solo si es complejo, gastamos la bala de plata.
        // Aquí entra en juego el KV Cache del manual.
        $solucion = $ai->resolverTicketComplejo($ticket);

        return response()->json([
            'source' => 'Expert AI',
            'analysis' => $solucion
        ]);
    }
}