<?php

namespace App\Libraries;

/**
 * EmailService — envia correos via SendGrid API (SDK v7) con click tracking desactivado.
 * Todos los envios del proyecto deben pasar por aqui.
 */
class EmailService
{
    protected string $fromEmail;
    protected string $fromName;
    protected string $apiKey;

    public function __construct()
    {
        $this->fromEmail = env('email.fromEmail') ?: 'noreply@cycloidtalent.com';
        $this->fromName  = env('email.fromName') ?: 'Censo APP';
        $this->apiKey    = (string) env('email.SMTPPass');
    }

    protected function sendViaSendGrid(string $toEmail, string $subject, string $htmlContent, array $options = []): bool
    {
        return $this->sendViaSendGridDetailed($toEmail, $subject, $htmlContent, $options)['success'];
    }

    /**
     * @return array{success: bool, status: int, message_id: string|null, error: string|null}
     */
    protected function sendViaSendGridDetailed(string $toEmail, string $subject, string $htmlContent, array $options = []): array
    {
        if ($this->apiKey === '') {
            log_message('error', 'SendGrid API key vacia; email a {to} no enviado', ['to' => $toEmail]);

            return ['success' => false, 'status' => 0, 'message_id' => null, 'error' => 'API key no configurada'];
        }

        try {
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($options['fromEmail'] ?? $this->fromEmail, $options['fromName'] ?? $this->fromName);
            $email->setSubject($subject);
            $email->addTo($toEmail);
            $email->addContent('text/html', $htmlContent);

            foreach ($options['cc'] ?? [] as $cc) {
                $email->addCc($cc);
            }

            foreach ($options['attachments'] ?? [] as $att) {
                $email->addAttachment(
                    $att['content'],
                    $att['type'] ?? 'application/pdf',
                    $att['filename'] ?? 'archivo.pdf',
                    'attachment'
                );
            }

            // CLAVE: desactivar click tracking para no reescribir URLs.
            $tracking = new \SendGrid\Mail\TrackingSettings();
            $click    = new \SendGrid\Mail\ClickTracking();
            $click->setEnable(false);
            $click->setEnableText(false);
            $tracking->setClickTracking($click);
            $email->setTrackingSettings($tracking);

            $sendgrid = new \SendGrid($this->apiKey);
            $response = $sendgrid->send($email);

            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                log_message('info', 'Email enviado a {to} ({subj})', ['to' => $toEmail, 'subj' => $subject]);

                $headers = $response->headers();
                $headerValue = $headers['X-Message-Id'] ?? $headers['x-message-id'] ?? null;
                $messageId = is_array($headerValue) ? ($headerValue[0] ?? null) : (is_string($headerValue) ? $headerValue : null);

                return ['success' => true, 'status' => $response->statusCode(), 'message_id' => $messageId, 'error' => null];
            }

            log_message('error', 'Error email {to} (HTTP {code}): {body}', [
                'to' => $toEmail, 'code' => $response->statusCode(), 'body' => $response->body(),
            ]);

            return ['success' => false, 'status' => $response->statusCode(), 'message_id' => null, 'error' => substr((string) $response->body(), 0, 1000)];
        } catch (\Throwable $e) {
            log_message('error', 'Excepcion email {to}: {msg}', ['to' => $toEmail, 'msg' => $e->getMessage()]);

            return ['success' => false, 'status' => 0, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envia una comunicacion asociada a un expediente de proteccion de datos.
     *
     * @return array{success: bool, status: int, message_id: string|null, error: string|null}
     */
    public function sendPrivacyMessage(string $toEmail, string $subject, string $html, ?string $pdfAbsPath = null, ?int $clientId = null): array
    {
        if ($clientId && ! $this->providerAllowed($clientId, 'sendgrid')) {
            return ['success' => false, 'status' => 0, 'message_id' => null, 'error' => 'V-01/V-07: SendGrid no tiene cadena contractual vigente para este Responsable'];
        }
        $attachments = [];
        if ($pdfAbsPath && is_file($pdfAbsPath)) {
            $content = (string) file_get_contents($pdfAbsPath);
            $writePath = rtrim(str_replace('\\', '/', WRITEPATH), '/') . '/';
            $normalized = str_replace('\\', '/', $pdfAbsPath);
            if (str_starts_with($normalized, $writePath)) {
                $relative = substr($normalized, strlen($writePath));
                $content = (new PrivacyPdf())->contents($relative);
            }
            $attachments[] = [
                'content' => base64_encode($content),
                'type' => 'application/pdf',
                'filename' => 'respuesta-proteccion-datos.pdf',
            ];
        }

        return $this->sendViaSendGridDetailed($toEmail, $subject, $html, ['attachments' => $attachments]);
    }

    /** Canal aislado para el segundo factor de autenticacion; no admite adjuntos. */
    public function sendMfaCode(string $toEmail, string $subject, string $html): array
    {
        return $this->sendViaSendGridDetailed($toEmail, $subject, $html);
    }

    /** Enlace de restablecimiento de contrasena. */
    public function sendPasswordReset(string $toEmail, string $nombre, string $link): bool
    {
        $html = view('emails/password_reset', ['nombre' => $nombre, 'link' => $link]);

        return $this->sendViaSendGrid($toEmail, 'Restablecer contrasena - Censo APP', $html);
    }

    /** Email de prueba para verificar configuracion. */
    public function sendTestEmail(string $toEmail): bool
    {
        $html = view('emails/test_email', ['testDate' => date('Y-m-d H:i:s')]);

        return $this->sendViaSendGrid($toEmail, 'Test de configuracion - Censo APP', $html);
    }

    /**
     * Envia el PDF del censo a los destinatarios dados.
     *
     * @param string[] $recipients
     *
     * @return int numero de envios exitosos
     */
    public function sendCensoPdf(array $recipients, array $cliente, string $instrumento, string $pdfAbsPath): int
    {
        $recipients = array_values(array_unique(array_filter(
            array_map(static fn ($e) => trim((string) $e), $recipients),
            static fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        )));

        $clientId = (int) ($cliente['id'] ?? 0);
        if ($recipients === [] || ! is_file($pdfAbsPath) || $clientId < 1
            || ! $this->providerAllowed($clientId, 'sendgrid')) {
            return 0;
        }

        $label   = $instrumento === 'mascotas' ? 'Censo de Mascotas' : 'Censo Poblacional';
        $subject = $label . ' - ' . ($cliente['nombre_tercero'] ?? 'Conjunto');
        $html    = view('emails/censo', ['cliente' => $cliente, 'label' => $label]);

        $attachment = [
            'content'  => base64_encode((string) file_get_contents($pdfAbsPath)),
            'type'     => 'application/pdf',
            'filename' => 'censo-' . $instrumento . '.pdf',
        ];

        $ok = 0;
        foreach ($recipients as $to) {
            if ($this->sendViaSendGrid($to, $subject, $html, ['attachments' => [$attachment]])) {
                $ok++;
            }
        }

        return $ok;
    }

    protected function providerAllowed(int $clientId, string $provider): bool
    {
        return (new PrivacyProcessorGate())->allowsProvider($clientId, $provider);
    }
}
