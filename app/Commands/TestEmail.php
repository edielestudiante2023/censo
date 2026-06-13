<?php

namespace App\Commands;

use App\Libraries\EmailService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestEmail extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:email';
    protected $description = 'Envia un email de prueba para verificar la configuracion SendGrid';
    protected $usage       = 'test:email [email_address]';
    protected $arguments   = ['email_address' => 'Email destinatario'];

    public function run(array $params)
    {
        $toEmail = $params[0] ?? CLI::prompt('Email destinatario');

        if (! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Email invalido');

            return;
        }

        CLI::write("Enviando test a: {$toEmail}", 'yellow');

        if ((new EmailService())->sendTestEmail($toEmail)) {
            CLI::write('Enviado. Revisa tu bandeja (incluye spam).', 'green');
        } else {
            CLI::error('Fallo. Revisa writable/logs/log-*.log');
        }
    }
}
