<?php

use App\Libraries\PrivacyRequestWorkflow;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyRequestWorkflowTest extends CIUnitTestCase
{
    public function testClassifiesEveryRightsProcedure(): void
    {
        $this->assertFalse(PrivacyRequestWorkflow::isComplaint('consulta'));
        foreach (['reclamo', 'rectificacion', 'actualizacion', 'revocatoria', 'supresion'] as $type) {
            $this->assertTrue(PrivacyRequestWorkflow::isComplaint($type));
            $this->assertTrue(PrivacyRequestWorkflow::canRequestCorrection($type));
        }
    }

    public function testUsesTenPlusFiveForQueriesAndFifteenPlusEightForComplaints(): void
    {
        $query = PrivacyRequestWorkflow::initialDeadline('consulta', '2026-07-10');
        $claim = PrivacyRequestWorkflow::initialDeadline('actualizacion', '2026-07-10');
        $this->assertSame('2026-07-27', $query);
        $this->assertSame('2026-08-03', PrivacyRequestWorkflow::extensionDeadline('consulta', $query));
        $this->assertSame('2026-08-03', $claim);
        $this->assertSame('2026-08-14', PrivacyRequestWorkflow::extensionDeadline('actualizacion', $claim));
    }

    public function testRejectsLateOrRepeatedExtensions(): void
    {
        $request = ['vence_at' => '2026-07-31', 'prorroga_hasta' => null, 'identidad_estado' => 'verificada'];
        $this->assertTrue(PrivacyRequestWorkflow::canExtend($request, '2026-07-31'));
        $this->assertFalse(PrivacyRequestWorkflow::canExtend($request, '2026-08-01'));
        $request['prorroga_hasta'] = '2026-08-12';
        $this->assertFalse(PrivacyRequestWorkflow::canExtend($request, '2026-07-30'));
    }

    public function testAbandonmentRunsFromCorrectionRequest(): void
    {
        $this->assertSame('2026-09-12 14:00:00', PrivacyRequestWorkflow::abandonmentLimit('2026-07-12 14:00:00'));
    }
}
