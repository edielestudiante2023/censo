<?php

use App\Libraries\PrivacyCipher;
use App\Libraries\PrivacyPii;
use App\Libraries\PrivacyVault;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyVaultTest extends CIUnitTestCase
{
    private function vault(): PrivacyVault
    {
        return new PrivacyVault(
            new PrivacyCipher([1 => str_repeat("\x51", 32)], 1),
            new PrivacyPii(bin2hex(str_repeat("\x61", 32)))
        );
    }

    public function testConsentRoundTripPreservesEvidenceHashAndBuildsBlindIndexes(): void
    {
        $html = '<article>Autorizacion individual CC 1020304</article>';
        $plain = [
            'instancia_html' => $html,
            'instancia_hash' => hash('sha256', $html),
            'titular_documento' => 'CC 1.020.304',
            'titular_email' => 'Titular@Correo.CO',
            'firma_imagen' => 'data:image/png;base64,AAAA',
        ];
        $vault = $this->vault();
        $encrypted = $vault->encryptRow('dp_consentimientos', $plain);

        $this->assertStringStartsWith('v1.', $encrypted['instancia_html']);
        $this->assertStringStartsWith('v1.', $encrypted['firma_imagen']);
        $this->assertSame($vault->blindLookup('dp_consentimientos', 'titular_documento', '1020304'), $encrypted['titular_documento_bidx']);
        $this->assertSame($vault->blindLookup('dp_consentimientos', 'titular_email', ' titular@correo.co '), $encrypted['titular_email_bidx']);

        $roundTrip = $vault->decryptRow('dp_consentimientos', $encrypted);
        $this->assertSame($plain, array_intersect_key($roundTrip, $plain));
        $this->assertTrue($vault->verifyHashInvariant('dp_consentimientos', $roundTrip));
    }

    public function testCiphertextCannotBeMovedBetweenFields(): void
    {
        $vault = $this->vault();
        $row = $vault->encryptRow('dp_solicitudes', [
            'titular_documento' => '1020304',
            'titular_email' => 'titular@example.com',
        ]);
        $row['titular_email'] = $row['titular_documento'];

        $this->expectException(\RuntimeException::class);
        $vault->decryptRow('dp_solicitudes', $row);
    }

    public function testEncryptedFileIsBoundToItsContext(): void
    {
        $vault = $this->vault();
        $blob = $vault->encryptFile("%PDF-1.4\0private", 'pdf|client-1/file.pdf.enc');
        $this->assertStringStartsWith("PRIVENC1\0", $blob);
        $this->assertSame("%PDF-1.4\0private", $vault->decryptFile($blob, 'pdf|client-1/file.pdf.enc'));

        $this->expectException(\RuntimeException::class);
        $vault->decryptFile($blob, 'pdf|client-2/file.pdf.enc');
    }
}
