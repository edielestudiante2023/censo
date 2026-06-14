<?php

use App\Libraries\HabeasData;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HabeasDataTest extends CIUnitTestCase
{
    public function testResolveUsesStandardTextWhenClientHasNoCustomText(): void
    {
        $resolved = HabeasData::resolve([
            'nombre_tercero' => 'Conjunto Prueba',
            'documento'      => '900123456-7',
            'email'          => 'admin@example.com',
        ]);

        $this->assertStringContainsString('Ley 1581 de 2012', $resolved);
        $this->assertStringContainsString('Conjunto Prueba', $resolved);
        $this->assertStringContainsString('900123456-7', $resolved);
        $this->assertStringContainsString('admin@example.com', $resolved);
        $this->assertStringNotContainsString('{NOMBRE_CONJUNTO}', $resolved);
    }

    public function testResolveUsesStandardTextWhenCustomTextIsBlank(): void
    {
        $resolved = HabeasData::resolve([
            'nombre_tercero'     => 'Conjunto Blanco',
            'documento'          => '901',
            'email'              => 'contacto@example.com',
            'texto_habeas_data'  => '   ',
        ]);

        $this->assertStringContainsString('Conjunto Blanco', $resolved);
        $this->assertStringContainsString('Ley 1581 de 2012', $resolved);
    }

    public function testResolveKeepsCustomTextWhenProvided(): void
    {
        $resolved = HabeasData::resolve([
            'nombre_tercero'     => 'Conjunto Custom',
            'documento'          => '902',
            'email'              => 'custom@example.com',
            'texto_habeas_data'  => 'Texto propio para {NOMBRE_CONJUNTO} con correo {CORREO_ADMIN}.',
        ]);

        $this->assertSame('Texto propio para Conjunto Custom con correo custom@example.com.', $resolved);
    }

    public function testLegacyDefaultTextIsReplacedByCurrentStandard(): void
    {
        $resolved = HabeasData::resolve([
            'nombre_tercero'     => 'Conjunto Legacy',
            'documento'          => '903',
            'email'              => 'legacy@example.com',
            'texto_habeas_data'  => 'Autorizo el tratamiento de mis datos a {NOMBRE_CONJUNTO} (NIT {NIT}) conforme a la Ley 1581 de 2012.',
        ]);

        $this->assertStringContainsString('Decreto 1377 de 2013', $resolved);
        $this->assertStringContainsString('Conjunto Legacy', $resolved);
        $this->assertStringContainsString('legacy@example.com', $resolved);
        $this->assertStringNotContainsString('Autorizo el tratamiento de mis datos a', $resolved);
    }
}
