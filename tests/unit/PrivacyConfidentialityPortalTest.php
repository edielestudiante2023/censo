<?php

use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyConfidentialityPortalTest extends CIUnitTestCase
{
    public function testPortalRequiresEndOfDocumentConfirmationBeforeSignature(): void
    {
        $view = file_get_contents(APPPATH . 'Views/privacy/confidentiality_portal.php');
        $controller = file_get_contents(APPPATH . 'Controllers/PrivacyConfidentialityController.php');
        $this->assertStringContainsString('id="confirm-view"', $view);
        $this->assertStringContainsString('disabled>Confirmar lectura completa', $view);
        $this->assertStringContainsString('legal.scrollTop+legal.clientHeight', $view);
        $this->assertStringContainsString("empty(\$agreement['vista_at'])", $controller);
        $this->assertStringContainsString('confirmacion_tras_scroll', $controller);
    }

    public function testSignedInstanceHasDatabaseImmutabilityGuard(): void
    {
        $migration = file_get_contents(APPPATH . 'Database/Migrations/2026-07-12-000014_ProtectSignedConfidentialityInstances.php');
        $this->assertStringContainsString('OLD.aceptado_at IS NOT NULL', $migration);
        $this->assertStringContainsString('OLD.instancia_html <=> NEW.instancia_html', $migration);
        $this->assertStringContainsString('OLD.instancia_hash <=> NEW.instancia_hash', $migration);
        $this->assertStringContainsString('La instancia firmada y su evidencia son inmutables', $migration);
    }

    public function testRoleFilterEnforcesSignedOperationsDuringSession(): void
    {
        $filter = file_get_contents(APPPATH . 'Filters/RoleFilter.php');
        $this->assertStringContainsString('allowsOperation', $filter);
        $this->assertStringContainsString("'exportar'", $filter);
        $this->assertStringContainsString("'suprimir'", $filter);
        $this->assertStringContainsString("'actualizar'", $filter);
        $this->assertStringContainsString('allowsPrivacyGovernance', $filter);
    }
}
