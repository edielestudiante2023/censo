<?php

use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyInstructionsTest extends CIUnitTestCase
{
    public function testProductionGuideDoesNotAskForFictitiousClientData(): void
    {
        $source = file_get_contents(APPPATH . 'Views/privacy/_instructions.php');

        $this->assertStringNotContainsString('copropiedad de prueba', mb_strtolower($source));
        $this->assertStringNotContainsString('datos ficticios', mb_strtolower($source));
        $this->assertStringNotContainsString('autorizacion ficticia', mb_strtolower($source));
        $this->assertStringContainsString('informacion institucional verificada', $source);
    }

    public function testGuideContentHasAConstrainedIndependentScrollArea(): void
    {
        $source = file_get_contents(APPPATH . 'Views/privacy/_instructions.php');

        $this->assertStringContainsString('.guide-main{min-width:0;min-height:0;overflow:hidden', $source);
        $this->assertStringContainsString('.guide-content{flex:1 1 auto;min-height:0', $source);
        $this->assertStringContainsString('overflow-y:auto', $source);
        $this->assertStringContainsString('height:100dvh', $source);
    }
}
