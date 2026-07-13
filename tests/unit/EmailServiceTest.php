<?php

use App\Libraries\EmailService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EmailServiceTest extends CIUnitTestCase
{
    public function testSendCensoPdfDeduplicatesAndFiltersRecipients(): void
    {
        $pdfPath = tempnam(WRITEPATH . 'cache', 'pdf-test-');
        $this->assertIsString($pdfPath);
        file_put_contents($pdfPath, '%PDF-1.4 test');

        try {
            $service = new FakeEmailService();

            $sent = $service->sendCensoPdf(
                [
                    'admin@example.com',
                    ' admin@example.com ',
                    'invalid-email',
                    '',
                    'residente@example.com',
                ],
                ['id' => 17, 'nombre_tercero' => 'Conjunto Demo'],
                'poblacional',
                $pdfPath
            );

            $this->assertSame(2, $sent);
            $this->assertSame(['admin@example.com', 'residente@example.com'], array_column($service->deliveries, 'to'));
            $this->assertSame('Censo Poblacional - Conjunto Demo', $service->deliveries[0]['subject']);
            $this->assertSame('censo-poblacional.pdf', $service->deliveries[0]['attachment']);
        } finally {
            @unlink($pdfPath);
        }
    }

    public function testSendCensoPdfDoesNotSendWithoutValidPdf(): void
    {
        $service = new FakeEmailService();

        $sent = $service->sendCensoPdf(
            ['admin@example.com'],
            ['nombre_tercero' => 'Conjunto Demo'],
            'mascotas',
            WRITEPATH . 'cache/no-existe.pdf'
        );

        $this->assertSame(0, $sent);
        $this->assertSame([], $service->deliveries);
    }

    public function testSendCensoPdfIsBlockedWhenProcessorAgreementIsMissing(): void
    {
        $pdfPath = tempnam(WRITEPATH . 'cache', 'pdf-test-');
        file_put_contents($pdfPath, '%PDF-1.4 test');
        try {
            $service = new BlockedEmailService();
            $this->assertSame(0, $service->sendCensoPdf(
                ['admin@example.com'],
                ['id' => 17, 'nombre_tercero' => 'Conjunto Demo'],
                'poblacional',
                $pdfPath
            ));
        } finally {
            @unlink($pdfPath);
        }
    }
}

class FakeEmailService extends EmailService
{
    /**
     * @var array<int, array{to: string, subject: string, attachment: string|null}>
     */
    public array $deliveries = [];

    protected function sendViaSendGrid(string $toEmail, string $subject, string $htmlContent, array $options = []): bool
    {
        $attachment = $options['attachments'][0]['filename'] ?? null;

        $this->deliveries[] = [
            'to'         => $toEmail,
            'subject'    => $subject,
            'attachment' => $attachment,
        ];

        return true;
    }

    protected function providerAllowed(int $clientId, string $provider): bool
    {
        return true;
    }
}

final class BlockedEmailService extends FakeEmailService
{
    protected function providerAllowed(int $clientId, string $provider): bool
    {
        return false;
    }
}
