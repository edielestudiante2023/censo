<?php

use App\Libraries\PrivacyHousingCoverage;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyHousingCoverageTest extends CIUnitTestCase
{
    public function testCurrentDecisionMarksUnitAsManagedRegardlessOfDecisionOutcome(): void
    {
        $this->assertSame('gestionada', PrivacyHousingCoverage::status([
            'total_decisiones' => 2,
            'decisiones_vigentes' => 1,
            'negadas' => 1,
        ], true));
    }

    public function testOnlyPreviousVersionMarksUnitAsOutdated(): void
    {
        $this->assertSame('desactualizada', PrivacyHousingCoverage::status([
            'total_decisiones' => 1,
            'decisiones_vigentes' => 0,
        ], true));
    }

    public function testUnitWithoutDecisionsIsPending(): void
    {
        $this->assertSame('pendiente', PrivacyHousingCoverage::status([
            'total_decisiones' => 0,
            'decisiones_vigentes' => 0,
        ], true));
    }

    public function testLabelIncludesTowerWhenPresent(): void
    {
        $this->assertSame('Torre 19 - 2404', PrivacyHousingCoverage::label([
            'torre_nombre' => 'Torre 19',
            'identificador' => '2404',
        ]));
        $this->assertSame('Casa 14', PrivacyHousingCoverage::label([
            'torre_nombre' => null,
            'identificador' => 'Casa 14',
        ]));
    }

    public function testSummaryMatchesConfiguredUnitsOnMysql(): void
    {
        $db = \Config\Database::connect('default');
        if ($db->DBDriver !== 'MySQLi' || ! $db->fieldExists('inmueble_id', 'dp_consentimientos')) {
            $this->markTestSkipped('Requiere MariaDB con la migracion de cobertura habitacional aplicada.');
        }
        $client = $db->table('inmuebles')->select('cliente_id, COUNT(*) AS total', false)
            ->where('deleted_at', null)->groupBy('cliente_id')->orderBy('total', 'DESC')->get()->getRowArray();
        if (! $client) {
            $this->markTestSkipped('No hay unidades configuradas para probar cobertura.');
        }
        $authorization = $db->table('dp_documentos')->select('id')->where('cliente_id', $client['cliente_id'])
            ->where('tipo', 'autorizacion')->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();

        $summary = (new PrivacyHousingCoverage($db))->summarize(
            (int) $client['cliente_id'],
            $authorization ? (int) $authorization['id'] : null
        );

        $this->assertSame((int) $client['total'], $summary['total']);
        $this->assertSame($summary['total'], $summary['gestionadas'] + $summary['desactualizadas'] + $summary['pendientes']);
        $this->assertCount($summary['pendientes'] + $summary['desactualizadas'], $summary['faltantes']);
    }
}
