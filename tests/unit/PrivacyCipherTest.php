<?php

use App\Libraries\PrivacyCipher;
use App\Libraries\PrivacyPii;
use CodeIgniter\Test\CIUnitTestCase;

final class PrivacyCipherTest extends CIUnitTestCase
{
    private function cipher(int $current = 1): PrivacyCipher
    {
        return new PrivacyCipher([
            1 => str_repeat("\x11", 32),
            2 => str_repeat("\x22", 32),
        ], $current);
    }

    public function testRoundTripRecoversPlaintext(): void
    {
        $c = $this->cipher();
        $plain = 'Cedula 1.020.304.050 de Juan Perez';
        $enc = $c->encrypt($plain);
        $this->assertNotSame($plain, $enc);
        $this->assertStringStartsWith('v1.', (string) $enc);
        $this->assertSame($plain, $c->decrypt($enc));
    }

    public function testNonceMakesCiphertextNonDeterministic(): void
    {
        $c = $this->cipher();
        $this->assertNotSame($c->encrypt('mismo-valor'), $c->encrypt('mismo-valor'));
        $this->assertSame('mismo-valor', $c->decrypt($c->encrypt('mismo-valor')));
    }

    public function testNullAndEmptyArePreserved(): void
    {
        $c = $this->cipher();
        $this->assertNull($c->encrypt(null));
        $this->assertSame('', $c->encrypt(''));
        $this->assertNull($c->decrypt(null));
    }

    public function testEncryptIsIdempotent(): void
    {
        $c = $this->cipher();
        $once = $c->encrypt('dato');
        $this->assertSame($once, $c->encrypt($once));
    }

    public function testLegacyPlaintextPassesThroughDecrypt(): void
    {
        $this->assertSame('valor-en-claro-heredado', $this->cipher()->decrypt('valor-en-claro-heredado'));
    }

    public function testTamperingCiphertextIsRejected(): void
    {
        $c = $this->cipher();
        $enc = (string) $c->encrypt('dato-integro');
        // Alterar un byte del cuerpo base64 debe romper la verificacion AEAD.
        $body = substr($enc, 3);
        $flippedChar = $body[10] === 'A' ? 'B' : 'A';
        $tampered = 'v1.' . substr_replace($body, $flippedChar, 10, 1);
        $this->expectException(\RuntimeException::class);
        $c->decrypt($tampered);
    }

    public function testCiphertextIsBoundToItsFieldContext(): void
    {
        $c = $this->cipher();
        $encrypted = $c->encrypt('titular@correo.co', 'dp_solicitudes|titular_email');
        $this->assertSame('titular@correo.co', $c->decrypt($encrypted, 'dp_solicitudes|titular_email'));
        $this->expectException(\RuntimeException::class);
        $c->decrypt($encrypted, 'dp_solicitudes|titular_documento');
    }

    public function testKeyRotationDecryptsOldAndNewVersions(): void
    {
        $encWithV1 = $this->cipher(1)->encrypt('historico');
        $this->assertStringStartsWith('v1.', (string) $encWithV1);

        $rotated = $this->cipher(2); // clave actual v2, pero conserva v1
        $this->assertSame('historico', $rotated->decrypt($encWithV1), 'Debe descifrar lo cifrado con la clave anterior.');
        $this->assertStringStartsWith('v2.', (string) $rotated->encrypt('nuevo'));
    }

    public function testBinaryRoundTrip(): void
    {
        $c = $this->cipher();
        $bin = random_bytes(2048);
        $blob = $c->encryptBinary($bin);
        $this->assertNotSame($bin, $blob);
        $this->assertSame($bin, $c->decryptBinary($blob));
    }

    public function testBlindIndexIsStableAndKeyedSeparately(): void
    {
        $pii = new PrivacyPii(bin2hex(str_repeat("\x33", 32)));
        $other = new PrivacyPii(bin2hex(str_repeat("\x44", 32)));

        // Estable e insensible al formato del documento
        $this->assertSame($pii->blindIndex('CC 1.020.304', 'documento'), $pii->blindIndex('1020304', 'documento'));
        // Email normalizado por mayusculas/espacios
        $this->assertSame($pii->blindIndex('Titular@Correo.CO', 'email'), $pii->blindIndex('  titular@correo.co ', 'email'));
        // Valores distintos -> indices distintos
        $this->assertNotSame($pii->blindIndex('1020304', 'documento'), $pii->blindIndex('9999999', 'documento'));
        // Clave independiente -> indices distintos para el mismo valor
        $this->assertNotSame($pii->blindIndex('1020304', 'documento'), $other->blindIndex('1020304', 'documento'));
        // Vacio -> null
        $this->assertNull($pii->blindIndex('', 'documento'));
    }
}
