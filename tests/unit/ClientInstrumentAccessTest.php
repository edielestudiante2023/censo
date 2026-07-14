<?php

use App\Libraries\ClientInstrumentAccess;
use CodeIgniter\Test\CIUnitTestCase;

final class ClientInstrumentAccessTest extends CIUnitTestCase
{
    public function testUnknownInstrumentIsDeniedBeforeDatabaseAccess(): void
    {
        $access = new ClientInstrumentAccess();

        $this->assertFalse($access->enabled(1, 'instrumento_inventado'));
        $source = file_get_contents(APPPATH . 'Libraries/ClientInstrumentAccess.php');
        $this->assertStringContainsString("'estado', 'habilitado'", $source);
        $this->assertStringContainsString('return false;', $source);
    }

    public function testCatalogContainsThreeIndependentValueAddedInstruments(): void
    {
        $this->assertSame([
            'censo_poblacional',
            'censo_mascotas',
            'tratamiento_datos',
        ], array_keys(ClientInstrumentAccess::LABELS));
    }

    public function testServerAndPublicEntryPointsEnforceEntitlements(): void
    {
        $filter = file_get_contents(APPPATH . 'Filters/RoleFilter.php');
        $qr = file_get_contents(APPPATH . 'Controllers/QrPublicController.php');
        $privacy = file_get_contents(APPPATH . 'Controllers/PrivacyPublicController.php');
        $dashboard = file_get_contents(APPPATH . 'Views/dashboard/index.php');

        $this->assertStringContainsString('requiredInstruments', $filter);
        $this->assertStringContainsString('Este instrumento no esta habilitado', $filter);
        $this->assertStringContainsString('ClientInstrumentAccess::POBLACIONAL', $qr);
        $this->assertStringContainsString('ClientInstrumentAccess::DATOS_PERSONALES', $privacy);
        $this->assertStringContainsString("instrumentos['tratamiento_datos']", $dashboard);
    }

    public function testDemoCommandsRequirePublishedDocumentsAndDecisions(): void
    {
        $prepare = file_get_contents(APPPATH . 'Commands/DemoPrepare.php');
        $preflight = file_get_contents(APPPATH . 'Commands/DemoPreflight.php');

        $this->assertStringContainsString('7 documentos publicados', $prepare);
        $this->assertStringContainsString('decisiones demostrativas', $prepare);
        $this->assertStringContainsString('DEMO LISTO', $preflight);
        $this->assertStringContainsString("where('estado', 'publicado')", $preflight);
    }
}
