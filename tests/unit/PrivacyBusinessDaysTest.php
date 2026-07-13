<?php

use App\Libraries\PrivacyBusinessDays;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyBusinessDaysTest extends CIUnitTestCase
{
    public function testAddsBusinessDaysSkippingWeekend(): void
    {
        $this->assertSame('2026-07-13', PrivacyBusinessDays::add('2026-07-10', 1));
        $this->assertSame('2026-07-27', PrivacyBusinessDays::add('2026-07-10', 10));
    }

    public function testClaimAndExtensionDeadlines(): void
    {
        $initial = PrivacyBusinessDays::add('2026-07-11', 15);
        $this->assertSame('2026-08-03', $initial);
        $this->assertSame('2026-08-14', PrivacyBusinessDays::add($initial, 8));
    }

    public function testUsesColombianHolidaysAndCutoff(): void
    {
        $this->assertFalse(PrivacyBusinessDays::isBusinessDay('2026-07-20'));
        $this->assertSame('2026-07-21', PrivacyBusinessDays::legalReceipt('2026-07-20 10:00:00'));
        $this->assertSame('2026-07-13', PrivacyBusinessDays::legalReceipt('2026-07-10 17:01:00'));
        $this->assertSame('2026-07-10', PrivacyBusinessDays::legalReceipt('2026-07-10 17:00:00'));
    }

    public function testCountsElapsedBusinessDaysForIncidentEscalation(): void
    {
        $this->assertSame(10, PrivacyBusinessDays::elapsed('2026-07-10', '2026-07-27'));
        $this->assertSame(15, PrivacyBusinessDays::elapsed('2026-07-10', '2026-08-03'));
    }
}
