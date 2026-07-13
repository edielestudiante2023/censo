<?php

use App\Libraries\PrivacyConfidentialityService;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyConfidentialityServiceTest extends CIUnitTestCase
{
    public function testBuildsResolvedIndividualInstanceAndVerifiesCanonicalHash(): void
    {
        $service = new PrivacyConfidentialityService();
        $instance = $service->build($this->data());

        foreach (['Ana Operadora', 'CC 123456', 'Residentes', 'Gestion de acceso', 'consultar, exportar',
            'Carlos Autorizador', '2027-07-12', 'Politica v3', 'Manual de Seguridad v2'] as $text) {
            $this->assertStringContainsString($text, $instance['html']);
        }
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $instance['hash']);
        $this->assertStringContainsString($instance['hash'], $instance['html']);
        $this->assertTrue($service->verify($instance['html'], $instance['hash']));
        $this->assertFalse($service->verify(str_replace('Gestion de acceso', 'Otra finalidad', $instance['html']), $instance['hash']));
        $this->assertDoesNotMatchRegularExpression('/\[[A-Z_]+\]|_{3,}/', $instance['html']);
    }

    public function testRendersOnlyApplicableConditionalClauses(): void
    {
        $data = $this->data();
        $data['flags'] = ['porteria' => true, 'video' => true, 'biometric' => false, 'sensitive' => false,
            'minors' => false, 'remote' => false, 'multi_tenant' => false, 'exports' => true];
        $html = (new PrivacyConfidentialityService())->build($data)['html'];

        foreach (['16-A. Porteria', '16-B. Videovigilancia', '16-H. Exportaciones'] as $heading) {
            $this->assertStringContainsString($heading, $html);
        }
        foreach (['16-C. Biometria', '16-D. Datos sensibles', '16-E. Datos de menores', '16-F. Trabajo remoto', '16-G. Varias copropiedades'] as $heading) {
            $this->assertStringNotContainsString($heading, $html);
        }
    }

    public function testSeparatesIndefinitePersonalDataDutyFromOtherConfidentialInformation(): void
    {
        $html = (new PrivacyConfidentialityService())->build($this->data())['html'];
        $this->assertStringContainsString('datos personales</strong> es indefinida y subsiste sin limite temporal', $html);
        $this->assertStringContainsString('otra informacion confidencial</strong> dura mientras conserve legitimamente ese caracter', $html);
        $this->assertStringNotContainsString('toda informacion es indefinida', $html);
    }

    public function testBiometricsAlwaysForcesSensitiveDataClause(): void
    {
        $data = $this->data();
        $data['flags']['biometric'] = true;
        $data['flags']['sensitive'] = false;
        $html = (new PrivacyConfidentialityService())->build($data)['html'];
        $this->assertStringContainsString('16-C. Biometria', $html);
        $this->assertStringContainsString('16-D. Datos sensibles', $html);
    }

    private function data(): array
    {
        return [
            'responsible' => 'Conjunto Demo', 'nit' => '900123456', 'signer' => 'Ana Operadora',
            'document_type' => 'CC', 'document_number' => '123456', 'link_type' => 'laboral',
            'role' => 'Porteria', 'authorizer' => 'Carlos Autorizador', 'valid_from' => '2026-07-12',
            'valid_until' => '2027-07-12', 'version' => 2, 'generated_at' => '2026-07-12 10:00:00',
            'versions' => ['politica' => 3, 'aviso' => 2, 'autorizacion' => 4, 'procedimiento' => 2, 'seguridad' => 2],
            'bases' => ['Residentes'], 'purposes' => ['Gestion de acceso'], 'operations' => ['consultar', 'exportar'],
            'flags' => ['porteria' => false, 'video' => false, 'biometric' => false, 'sensitive' => false,
                'minors' => false, 'remote' => false, 'multi_tenant' => false, 'exports' => false],
            'incident_channel' => 'incidentes@example.com', 'privacy_email' => 'privacidad@example.com',
            'privacy_phone' => '6010000000',
        ];
    }
}
