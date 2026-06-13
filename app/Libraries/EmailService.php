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
        if ($this->apiKey === '') {
            log_message('error', 'SendGrid API key vacia; email a {to} no enviado', ['to' => $toEmail]);

            return false;
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

                return true;
            }

            log_message('error', 'Error email {to} (HTTP {code}): {body}', [
                'to' => $toEmail, 'code' => $response->statusCode(), 'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            log_message('error', 'Excepcion email {to}: {msg}', ['to' => $toEmail, 'msg' => $e->getMessage()]);

            return false;
        }
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

        if ($recipients === [] || ! is_file($pdfAbsPath)) {
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
}
