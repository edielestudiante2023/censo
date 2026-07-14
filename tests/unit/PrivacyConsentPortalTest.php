<?php

use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyConsentPortalTest extends CIUnitTestCase
{
    public function testPortalUsesGranularDecisionsAndEmailVerification(): void
    {
        $source = file_get_contents(APPPATH . 'Views/privacy/public_portal.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('finalidad_decision[', $source);
        $this->assertStringContainsString('/consentimiento/codigo', $source);
        $this->assertStringContainsString('/consentimiento/verificar', $source);
        $this->assertStringContainsString('sensible_decision[', $source);
        $this->assertStringContainsString('transferencia_decision', $source);
        $this->assertStringContainsString('soporte_representacion', $source);
        $this->assertStringContainsString('Instancia final para firma', $source);
        $this->assertStringContainsString('name="inmueble_id"', $source);
        $this->assertStringContainsString('housing-unit-field', $source);
        $this->assertStringContainsString('action" value="preview', $source);
        $this->assertStringContainsString('action" value="confirm', $source);
        $this->assertStringNotContainsString('name="decision"', $source);
        $this->assertStringNotContainsString('Autorizo todas', $source);
    }

    public function testConsentGateChecksExclusionsVectorAndRevocations(): void
    {
        $source = file_get_contents(APPPATH . 'Libraries/PrivacyConsentGate.php');
        $this->assertStringContainsString('dp_exclusiones', $source);
        $this->assertStringContainsString('decision_vector_json', $source);
        $this->assertStringContainsString("->where('tipo', 'revocatoria')", $source);
    }

    public function testAppendOnlyMigrationDefinesDatabaseGuards(): void
    {
        $source = file_get_contents(APPPATH . 'Database/Migrations/2026-07-12-000005_CompletePrivacyConsentControls.php');
        $this->assertStringContainsString('BEFORE UPDATE ON dp_consentimientos', $source);
        $this->assertStringContainsString('BEFORE DELETE ON dp_consentimientos', $source);
        $this->assertStringContainsString('BEFORE UPDATE ON dp_consentimiento_eventos', $source);
        $this->assertStringContainsString('append-only', $source);
    }

    public function testHousingUnitIsSealedAndTenantScoped(): void
    {
        $controller = file_get_contents(APPPATH . 'Controllers/PrivacyPublicController.php');
        $migration = file_get_contents(APPPATH . 'Database/Migrations/2026-07-13-000022_LinkPrivacyConsentsToHousingUnits.php');
        $dashboard = file_get_contents(APPPATH . 'Views/privacy/index.php');

        $this->assertStringContainsString("'inmueble_id' => \$data['inmueble_id']", $controller);
        $this->assertStringContainsString('Unidad habitacional declarada:', $controller);
        $this->assertStringContainsString('FOREIGN KEY (`cliente_id`, `inmueble_id`)', $migration);
        $this->assertStringContainsString('Cobertura por unidad habitacional', $dashboard);
        $this->assertStringContainsString('Unidades que requieren gestion', $dashboard);
    }
}
