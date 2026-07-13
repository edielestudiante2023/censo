<?php

use App\Libraries\PrivacyRestoreGuard;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyRestoreGuardTest extends CIUnitTestCase
{
    public function testFiltersExcludedIdentifiersBeforeRestoration(): void
    {
        $guard = new PrivacyRestoreGuard('test-secret');
        $excluded = $guard->identifierHash('1.234');
        $result = $guard->partition([
            ['documento' => '1.234', 'nombre' => 'Bloqueado'],
            ['documento' => '9.876', 'nombre' => 'Permitido'],
        ], static fn (array $row): string => $row['documento'], [$excluded]);

        $this->assertCount(1, $result['accepted']);
        $this->assertSame('Permitido', $result['accepted'][0]['nombre']);
        $this->assertCount(1, $result['blocked']);
        $this->assertSame($excluded, $result['blocked'][0]['identifier_hash']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['exclusion_version_hash']);
    }
}
