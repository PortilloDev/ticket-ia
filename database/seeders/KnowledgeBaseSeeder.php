<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KnowledgeBase;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Simulamos que la empresa tiene documentados estos procedimientos
        $manual = [
            ['topic' => 'Errores de Login', 'content' => 'Si el usuario recibe error 401, el token ha caducado. Pedir que haga logout y login. Si es 403, no tiene permisos.'],
            ['topic' => 'Políticas de Facturación', 'content' => 'Los reembolsos solo se aprueban si el ticket se abre en los primeros 14 días. Tardan 5 días hábiles en procesarse.'],
            ['topic' => 'Límites de API', 'content' => 'El rate limit global es de 60 peticiones por minuto por IP. Devuelve cabecera Retry-After.'],
            // ... imagina aquí miles de registros ...
        ];

        foreach ($manual as $entry) {
            KnowledgeBase::create($entry);
        }
    }
}
