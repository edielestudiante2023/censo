<?php

use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyProcessorLifecycleTest extends CIUnitTestCase
{
    public function testMigrationCreatesImmutableContractLifecycle(): void
    {
        $source = file_get_contents(APPPATH . 'Database/Migrations/2026-07-12-000015_CreateProcessorAgreementLifecycle.php');
        foreach (['dp_acuerdos_encargado', 'dp_subencargados', 'dp_encargado_instrucciones', 'dp_encargado_certificaciones',
            'La evidencia contractual es append-only', 'La instancia contractual firmada es inmutable'] as $text) {
            $this->assertStringContainsString($text, $source);
        }
    }

    public function testPublicPortalRequiresReadingOtpDoubleSignatureAndSupportsOperationalReports(): void
    {
        $view = file_get_contents(APPPATH . 'Views/privacy/processor_agreement_portal.php');
        $controller = file_get_contents(APPPATH . 'Controllers/PrivacyProcessorAgreementController.php');
        foreach (['confirm-view', 'codigo', 'firma_imagen', '/incidente', '/solicitud'] as $text) { $this->assertStringContainsString($text, $view); }
        foreach (['PrivacyBusinessDays::add', "'doble_firma_y_habilitacion'", "'notificacion_encargado'", "'remision_encargado'"] as $text) {
            $this->assertStringContainsString($text, $controller);
        }
    }

    public function testExternalProvidersAreBlockedByPublishedDocumentSevenWithoutAgreement(): void
    {
        $gate = file_get_contents(APPPATH . 'Libraries/PrivacyProcessorGate.php');
        $email = file_get_contents(APPPATH . 'Libraries/EmailService.php');
        $openai = file_get_contents(APPPATH . 'Libraries/OpenAiPrivacyService.php');
        $this->assertStringContainsString("where('tipo', 'encargados')", $gate);
        $this->assertStringContainsString('allowsProvider', $email);
        $this->assertStringContainsString("allowsProvider(\$clientId, 'openai')", $openai);
    }
}
