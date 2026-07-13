<?php

use App\Libraries\OpenAiPrivacyService;
use CodeIgniter\Test\CIUnitTestCase;

final class OpenAiPrivacyFilterTest extends CIUnitTestCase
{
    public function testAllowsStructuralInventoryWithoutIdentities(): void
    {
        (new OpenAiPrivacyService())->assertNoPersonalIdentifiers([
            'bases' => [['nombre' => 'Residentes', 'categorias' => ['Identificacion', 'Contacto']]],
            'finalidades' => [['descripcion' => 'Gestionar comunicaciones administrativas']],
        ]);
        $this->addToAssertionCount(1);
    }

    /** @dataProvider identifiers */
    public function testBlocksPossiblePersonalIdentifiers(string $value): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FILTRO_IDENTIDAD');
        (new OpenAiPrivacyService())->assertNoPersonalIdentifiers(['descripcion' => $value]);
    }

    public static function identifiers(): array
    {
        return [['ana@example.com'], ['CC 1020304050'], ['telefono: 3001234567'], ['Titular: Ana Perez']];
    }
}
