<?php

use App\Libraries\PrivacyDocumentService;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyDocumentServiceTest extends CIUnitTestCase
{
    private array $cliente = ['id' => 1, 'nombre_tercero' => 'Conjunto Demo', 'documento' => '900123456'];
    private array $programa = [
        'responsable_nombre' => 'Conjunto Demo', 'responsable_documento' => '900123456',
        'responsable_direccion' => 'Calle 1', 'responsable_ciudad' => 'Bogota',
        'canal_email' => 'privacidad@example.com', 'canal_telefono' => '6010000000',
        'oficial_nombre' => 'Administracion',
        'config_json' => '{"area_responsable":"Administracion","horario_atencion":"Lunes a viernes","organo_aprobacion":"Consejo de Administracion","fecha_aprobacion":"2026-07-12","fecha_vigencia":"2026-08-01","medio_publicacion":"Portal web","url_politica":"https://example.com/politica"}',
    ];
    private array $bases = [[
        'id' => 10, 'nombre' => 'Residentes', 'tipos_titular_json' => '["Residentes"]',
        'categorias_datos_json' => '["Identificacion","Contacto"]', 'retencion_meses' => 24,
        'criterio_eliminacion' => null, 'finalidad_resumen' => 'Administracion',
    ]];
    private array $purposes = [[
        'id' => 20, 'base_id' => 10, 'descripcion' => 'Gestionar comunicaciones administrativas.',
        'es_opcional' => 0, 'base_juridica_tipo' => 'autorizacion',
        'categorias_datos_json' => '["Identificacion","Contacto"]',
    ]];

    public function testRendersEveryMasterDocumentWithResponsibleAndChannel(): void
    {
        $service = new PrivacyDocumentService();
        foreach (['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad', 'confidencialidad', 'encargados'] as $type) {
            $html = $service->render($type, $this->cliente, $this->programa, $this->bases, $this->purposes);
            $this->assertStringContainsString('Conjunto Demo', $html, $type);
            $this->assertStringContainsString('privacidad@example.com', $html, $type);
            $this->assertStringContainsString('legal-document', $html, $type);
        }
    }

    public function testEscapesInventoryValues(): void
    {
        $bases = $this->bases;
        $bases[0]['nombre'] = '<script>alert(1)</script>';
        $html = (new PrivacyDocumentService())->render('politica', $this->cliente, $this->programa, $bases, $this->purposes);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testPolicyDoesNotPublishInternalRetentionInventory(): void
    {
        $html = (new PrivacyDocumentService())->render('politica', $this->cliente, $this->programa, $this->bases, $this->purposes);
        $this->assertStringContainsString('Categorias de Titulares, datos y finalidades', $html);
        $this->assertStringNotContainsString('<th>Conservacion</th>', $html);
        $this->assertStringNotContainsString('24 meses', $html);
    }

    public function testNoticeVariantsFollowConfiguredTreatments(): void
    {
        $program = $this->programa;
        $config = json_decode($program['config_json'], true);
        $config += [
            'usa_videovigilancia' => 1, 'graba_videovigilancia' => 1, 'plazo_grabaciones_dias' => 20,
            'usa_biometria' => 0, 'video_identificacion_biometrica' => 0,
        ];
        $program['config_json'] = json_encode($config);
        $variants = (new PrivacyDocumentService())->renderNoticeVariants($this->cliente, $program, $this->bases, $this->purposes);
        $this->assertArrayHasKey('formulario', $variants);
        $this->assertArrayHasKey('porteria', $variants);
        $this->assertArrayHasKey('videovigilancia', $variants);
        $this->assertArrayNotHasKey('biometria', $variants);
        $this->assertStringContainsString('20 dias', $variants['videovigilancia']['html']);
    }

    public function testAuthorizationHasGranularDecisionsWithoutGeneralConsent(): void
    {
        $html = (new PrivacyDocumentService())->render('autorizacion', $this->cliente, $this->programa, $this->bases, $this->purposes);
        $this->assertStringContainsString('Tratamientos informados que no dependen', $html);
        $this->assertStringContainsString('Autorizo</strong> o <strong>No autorizo', $html);
        $this->assertStringContainsString('no existe una decision general', $html);
        $this->assertStringNotContainsString('Autorizo parcialmente', $html);
    }

    public function testAuthorizationSeparatesDocumentedLegalExceptions(): void
    {
        $purposes = $this->purposes;
        $purposes[0]['base_juridica_tipo'] = 'excepcion_legal';
        $purposes[0]['base_juridica_detalle'] = 'Articulo 10 de la Ley 1581 de 2012.';
        $html = (new PrivacyDocumentService())->render('autorizacion', $this->cliente, $this->programa, $this->bases, $purposes);
        $this->assertStringContainsString('Articulo 10 de la Ley 1581', $html);
        $this->assertStringContainsString('La negativa no los impide', $html);
    }

    public function testAuthorizationRendersEachSensitiveDatumSeparately(): void
    {
        $bases = $this->bases;
        $bases[0]['datos_sensibles'] = 1;
        $purposes = $this->purposes;
        $purposes[0]['datos_sensibles_detalle'] = [[
            'dato' => 'Condicion de salud', 'finalidad_exclusiva' => 'Atender una emergencia informada por el residente.',
        ]];
        $html = (new PrivacyDocumentService())->render('autorizacion', $this->cliente, $this->programa, $bases, $purposes);
        $this->assertStringContainsString('Condicion de salud', $html);
        $this->assertStringContainsString('Atender una emergencia', $html);
        $this->assertStringContainsString('Autorizo expresamente o No autorizo', $html);
    }

    public function testRightsProcedureContainsAllRoutesAndExactTerms(): void
    {
        $html = (new PrivacyDocumentService())->render('procedimiento', $this->cliente, $this->programa, $this->bases, $this->purposes);
        foreach (['Consultas', 'Reclamos: tramite comun', 'Rectificacion y actualizacion', 'Revocatoria y supresion',
            'diez (10) dias habiles', 'quince (15) dias habiles', 'cinco (5) dias habiles adicionales',
            'ocho (8) dias habiles', 'dos (2) meses', 'reclamo en tramite', 'hash del valor anterior'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString('no se exigen hechos, motivos ni documentos', $html);
    }

    public function testSecurityManualHasSixteenOperationalChaptersAndIncidentDeadline(): void
    {
        $html = (new PrivacyDocumentService())->render('seguridad', $this->cliente, $this->programa, $this->bases, $this->purposes);
        foreach (range(1, 16) as $chapter) {
            $this->assertMatchesRegularExpression('/<h2>' . $chapter . '\./', $html);
        }
        $this->assertStringContainsString('quince (15) dias habiles', $html);
        $this->assertStringContainsString('no existe', $html);
        $this->assertStringContainsString('reaplica automaticamente la lista de exclusiones', $html);
        $this->assertStringNotContainsString('[[SI_CONFIG', $html);
        $this->assertStringNotContainsString('[TABLA_INVENTARIO]', $html);
    }

    public function testSecurityManualRendersOnlyConfiguredConditionalControls(): void
    {
        $program = $this->programa;
        $config = json_decode($program['config_json'], true);
        $config += ['usa_biometria' => 1, 'alternativa_biometrica' => 'Tarjeta individual',
            'plazo_supresion_biometria_dias' => 5, 'security_trabajo_remoto' => 0];
        $program['config_json'] = json_encode($config);
        $html = (new PrivacyDocumentService())->render('seguridad', $this->cliente, $program, $this->bases, $this->purposes);
        $this->assertStringContainsString('<h3>8.8 Biometria</h3>', $html);
        $this->assertStringContainsString('Tarjeta individual', $html);
        $this->assertStringNotContainsString('10.6 Trabajo remoto', $html);
    }

    public function testConfidentialityMasterCannotBeSignedAndHasNoOpenBlanks(): void
    {
        $html = (new PrivacyDocumentService())->render('confidencialidad', $this->cliente, $this->programa, $this->bases, $this->purposes);
        foreach (['No se firma esta plantilla', 'autorizador distinto del firmante', 'Cambio, recertificacion y baja',
            'reserva sobre datos personales es indefinida', 'codigo al correo', 'hash SHA-256', 'eventos son de solo adicion'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertDoesNotMatchRegularExpression('/\[[A-Z_]+\]|\[\[SI_|_{3,}/', $html);
    }

    public function testProcessorMasterHasTwentyOneRulesAndCorrectCircularReferences(): void
    {
        $html = (new PrivacyDocumentService())->render('encargados', $this->cliente, $this->programa, $this->bases, $this->purposes);
        foreach (range(1, 21) as $clause) { $this->assertMatchesRegularExpression('/<h2>' . $clause . '\./', $html); }
        $this->assertStringContainsString('No se firma esta plantilla', $html);
        $this->assertStringContainsString('Circular 002 de 2025 trata transferencia de tecnologia', $html);
        $this->assertStringContainsString('Circular 003 de 2025', $html);
        $this->assertStringNotContainsString('[DETALLAR]', $html);
        $this->assertStringNotContainsString('________________', $html);
    }
}
