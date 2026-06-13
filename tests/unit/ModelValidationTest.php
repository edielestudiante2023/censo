<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ModelValidationTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{0: class-string}>
     */
    public static function businessModelProvider(): iterable
    {
        yield 'ClienteModel' => [App\Models\ClienteModel::class];
        yield 'UsuarioModel' => [App\Models\UsuarioModel::class];
        yield 'RolModel' => [App\Models\RolModel::class];
        yield 'TorreModel' => [App\Models\TorreModel::class];
        yield 'InmuebleModel' => [App\Models\InmuebleModel::class];
        yield 'QrCodeModel' => [App\Models\QrCodeModel::class];
        yield 'TipoDocumentoModel' => [App\Models\TipoDocumentoModel::class];
        yield 'ParentescoModel' => [App\Models\ParentescoModel::class];
        yield 'TipoVehiculoModel' => [App\Models\TipoVehiculoModel::class];
        yield 'TipoMascotaModel' => [App\Models\TipoMascotaModel::class];
        yield 'CensoPoblacionalModel' => [App\Models\CensoPoblacionalModel::class];
        yield 'CensoPropietarioModel' => [App\Models\CensoPropietarioModel::class];
        yield 'CensoArrendatarioModel' => [App\Models\CensoArrendatarioModel::class];
        yield 'CensoResidenteModel' => [App\Models\CensoResidenteModel::class];
        yield 'CensoVehiculoModel' => [App\Models\CensoVehiculoModel::class];
        yield 'CensoTelefonoModel' => [App\Models\CensoTelefonoModel::class];
        yield 'CensoMascotaModel' => [App\Models\CensoMascotaModel::class];
        yield 'MascotaModel' => [App\Models\MascotaModel::class];
    }

    /**
     * @dataProvider businessModelProvider
     *
     * @param class-string $modelClass
     */
    public function testBusinessModelsDeclareValidationRules(string $modelClass): void
    {
        $properties = (new ReflectionClass($modelClass))->getDefaultProperties();

        $this->assertArrayHasKey('validationRules', $properties);
        $this->assertIsArray($properties['validationRules']);
        $this->assertNotEmpty($properties['validationRules'], $modelClass . ' debe declarar validationRules.');
    }
}
