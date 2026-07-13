<?php

use App\Libraries\PrivacyProcessorAgreementService;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyProcessorAgreementServiceTest extends CIUnitTestCase
{
    public function testBuildsTwentyOneClausesSixResolvedAnnexesAndCanonicalHash(): void
    {
        $service = new PrivacyProcessorAgreementService(); $instance = $service->build($this->data());
        foreach (range(1, 21) as $clause) { $this->assertMatchesRegularExpression('/<h2>' . $clause . '\./', $instance['html']); }
        foreach (['Anexo A.', 'Anexo B.', 'Anexo C.', 'Anexo D.', 'Anexo E.', 'Anexo F.'] as $annex) { $this->assertStringContainsString($annex, $instance['html']); }
        foreach (['Encargado Demo', 'Residentes', 'Gestionar acceso', 'Estados Unidos', 'SendGrid', 'Circular Externa SIC 003 de 2025'] as $text) {
            $this->assertStringContainsString($text, $instance['html']);
        }
        $this->assertTrue($service->verify($instance['html'], $instance['hash']));
        $this->assertFalse($service->verify(str_replace('Gestionar acceso', 'Finalidad alterada', $instance['html']), $instance['hash']));
        $this->assertDoesNotMatchRegularExpression('/\[[A-Z_]+\]|\[DETALLAR\]|_{3,}/', $instance['html']);
    }

    public function testSpecialCategoriesRenderOnlyWhenDeclared(): void
    {
        $data = $this->data(); $data['flags'] = ['sensitive' => false, 'biometric' => false, 'video' => false, 'minors' => false];
        $html = (new PrivacyProcessorAgreementService())->build($data)['html'];
        $this->assertStringContainsString('No se declararon categorias especiales', $html);
        $this->assertStringNotContainsString('plantillas cifradas y separadas', $html);
        $data['flags']['biometric'] = true;
        $html = (new PrivacyProcessorAgreementService())->build($data)['html'];
        $this->assertStringContainsString('plantillas cifradas y separadas', $html);
    }

    public function testNoSubprocessorProducesExplicitNegativeDeclaration(): void
    {
        $data = $this->data(); $data['subprocessors'] = [];
        $html = (new PrivacyProcessorAgreementService())->build($data)['html'];
        $this->assertStringContainsString('declara que no utiliza subencargados', $html);
    }

    private function data(): array
    {
        return ['responsible' => 'Conjunto Demo', 'responsible_id' => '900123456-1', 'responsible_representative' => 'Ana Admin',
            'processor' => 'Encargado Demo', 'processor_id' => '901111111-2', 'processor_representative' => 'Luis Legal',
            'processor_representative_id' => 'CC 123', 'representation_evidence' => 'Certificado 2026-07', 'main_contract' => 'CTR-2026-01',
            'service' => 'Alojamiento y operacion controlada del sistema de acceso de la copropiedad.', 'bases' => ['Residentes'],
            'holders' => ['residentes'], 'categories' => ['identificacion', 'biometricos'], 'purposes' => ['Gestionar acceso'],
            'operations' => ['consulta', 'biometria'], 'systems' => ['SaaS de acceso'], 'countries' => ['Colombia', 'Estados Unidos'],
            'classification' => 'encargado', 'classification_justification' => 'Opera solo bajo instrucciones.', 'classification_date' => '2026-07-12',
            'classification_evaluator' => 'Ana Admin', 'classification_answers' => ['solo_instrucciones' => true], 'risk' => 'alto',
            'measures' => ['control acceso', 'MFA', 'cifrado', 'no resucitar'], 'log_months' => 12, 'backup_days' => 90,
            'rto' => 12, 'rpo' => 4, 'rights_channel' => 'privacidad@example.com', 'incident_channel' => 'incidentes@example.com',
            'incident_days' => 2, 'instruction_channel' => 'contratos@example.com', 'country_check_date' => '2026-07-12',
            'subprocessors' => [['nombre' => 'SendGrid', 'documento' => 'US-ID', 'pais' => 'Estados Unidos', 'servicio' => 'Correo',
                'datos' => ['nombre', 'correo'], 'contrato_evidencia' => 'DPA-1', 'aprobado_at' => '2026-07-12']],
            'flags' => ['sensitive' => true, 'biometric' => true, 'video' => false, 'minors' => false], 'insurance' => 'Poliza P-1',
            'valid_from' => '2026-07-12', 'valid_until' => '2027-07-11',
            'versions' => ['politica' => 1, 'aviso' => 1, 'autorizacion' => 1, 'procedimiento' => 1, 'seguridad' => 1, 'confidencialidad' => 1],
            'instance_version' => 1, 'generated_at' => '2026-07-12 12:00:00', 'volume' => 'Diario, 500 registros',
            'access_profiles' => 'Soporte nominal autorizado'];
    }
}
