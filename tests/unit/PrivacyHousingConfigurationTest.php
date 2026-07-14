<?php

use App\Libraries\HousingUnitConfigurator;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyHousingConfigurationTest extends CIUnitTestCase
{
    public function testRejectsInvalidHouseRangeBeforeWriting(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new HousingUnitConfigurator())->generateHouses(1, [
            'prefix' => 'Casa', 'padding' => 0, 'from' => 20, 'to' => 10,
        ]);
    }

    public function testRejectsApartmentBatchAboveSafetyLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new HousingUnitConfigurator())->generateApartments(1, [
            'tower_prefix' => 'Torre', 'tower_from' => 1, 'tower_to' => 100,
            'floors' => 100, 'units_per_floor' => 2, 'unit_from' => 1,
        ]);
    }

    public function testPrivacyModuleOwnsHousingConfigurationRoutesAndInterface(): void
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $view = file_get_contents(APPPATH . 'Views/privacy/index.php');
        $instructions = file_get_contents(APPPATH . 'Views/privacy/_instructions.php');

        $this->assertStringContainsString("datos-personales/unidades/casas", $routes);
        $this->assertStringContainsString("datos-personales/unidades/apartamentos", $routes);
        $this->assertStringContainsString('Configurar unidades habitacionales', $view);
        $this->assertStringContainsString('housing_tower_from', $view);
        $this->assertStringContainsString('housing_house_from', $view);
        $this->assertStringContainsString('Configurar las unidades habitacionales', $instructions);
        $this->assertStringNotContainsString('censo poblacional', strtolower($view));
        $this->assertStringNotContainsString('copropiedad de prueba', strtolower($instructions));
    }
}
