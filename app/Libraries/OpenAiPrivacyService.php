<?php

namespace App\Libraries;

final class OpenAiPrivacyService
{
    public function configured(): bool
    {
        return trim((string) env('openai.apiKey')) !== '';
    }

    public function reviewInventory(array $bases, array $finalidades, int $clientId): array
    {
        if (! $this->configured()) {
            throw new \RuntimeException('La clave de OpenAI no esta configurada.');
        }
        if (! (new PrivacyProcessorGate())->allowsProvider($clientId, 'openai')) {
            throw new \RuntimeException('V-01/V-07: OpenAI no tiene cadena contractual vigente para este Responsable.');
        }

        $input = [
            'bases' => array_map(static fn (array $base): array => [
                'id' => (int) $base['id'],
                'nombre' => $base['nombre'],
                'titulares' => json_decode($base['tipos_titular_json'] ?? '[]', true),
                'categorias' => json_decode($base['categorias_datos_json'] ?? '[]', true),
                'sensibles' => (bool) $base['datos_sensibles'],
                'menores' => (bool) $base['datos_menores'],
                'retencion_meses' => $base['retencion_meses'],
            ], $bases),
            'finalidades' => array_map(static fn (array $purpose): array => [
                'base_id' => (int) $purpose['base_id'],
                'descripcion' => $purpose['descripcion'],
                'opcional' => (bool) $purpose['es_opcional'],
                'consentimiento_explicito' => (bool) $purpose['requiere_consentimiento_explicito'],
            ], $finalidades),
        ];
        $this->assertNoPersonalIdentifiers($input);

        $schema = [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => [
                'resumen' => ['type' => 'string'],
                'riesgos' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'properties' => [
                    'severidad' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']],
                    'base_id' => ['type' => ['integer', 'null']],
                    'hallazgo' => ['type' => 'string'],
                    'recomendacion' => ['type' => 'string'],
                ], 'required' => ['severidad', 'base_id', 'hallazgo', 'recomendacion']]],
                'preguntas_pendientes' => ['type' => 'array', 'items' => ['type' => 'string']],
                'finalidades_sugeridas' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'properties' => [
                    'base_id' => ['type' => 'integer'], 'texto' => ['type' => 'string'], 'justificacion' => ['type' => 'string'],
                ], 'required' => ['base_id', 'texto', 'justificacion']]],
            ],
            'required' => ['resumen', 'riesgos', 'preguntas_pendientes', 'finalidades_sugeridas'],
        ];

        $payload = [
            'model' => env('openai.model') ?: 'gpt-4o-mini',
            'instructions' => 'Actua como revisor de calidad de un inventario colombiano de proteccion de datos. No emitas una aprobacion legal ni inventes hechos. Identifica vacios, contradicciones, finalidades demasiado amplias y riesgos de minimizacion, retencion, seguridad, datos sensibles y menores. Trabaja solo con la informacion suministrada.',
            'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'revision_inventario', 'strict' => true, 'schema' => $schema]],
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . env('openai.apiKey'), 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException('OpenAI no pudo completar la revision' . ($error ? ': ' . $error : ' (HTTP ' . $status . ')'));
        }

        $response = json_decode($raw, true);
        $text = $response['output_text'] ?? null;
        if (! is_string($text)) {
            foreach ($response['output'] ?? [] as $output) {
                foreach ($output['content'] ?? [] as $content) {
                    if (($content['type'] ?? '') === 'output_text') {
                        $text = $content['text'] ?? null;
                        break 2;
                    }
                }
            }
        }
        $result = is_string($text) ? json_decode($text, true) : null;
        if (! is_array($result)) {
            throw new \RuntimeException('OpenAI devolvio una respuesta que no cumple el esquema esperado.');
        }
        return ['model' => $payload['model'], 'input_hash' => hash('sha256', json_encode($input)), 'result' => $result];
    }

    public function assertNoPersonalIdentifiers(array $input): void
    {
        $text = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $patterns = [
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu',
            '/\b(?:CC|CE|TI|documento|cedula|telefono|celular)\s*[:#-]?\s*\d{5,12}\b/iu',
            '/(?<!\d)\d{7,12}(?!\d)/',
            '/\b(?:titular|residente|propietario)\s*[:#-]\s*[\p{L}]+(?:\s+[\p{L}]+){1,4}\b/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, (string) $text)) {
                throw new \RuntimeException('FILTRO_IDENTIDAD: el inventario contiene un posible dato personal; se bloqueo el envio a OpenAI.');
            }
        }
    }
}
