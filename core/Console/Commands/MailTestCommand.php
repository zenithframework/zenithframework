<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MailTestCommand extends Command
{
    protected string $name = 'mail:test';

    protected string $description = 'Send test email';

    public function handle(Container $container, array $arguments): void
    {
        $to = $arguments[0] ?? null;

        if ($to === null) {
            $to = env('MAIL_TO');
        }

        if ($to === null) {
            $to = $this->ask('Enter recipient email address');
        }

        if (empty($to)) {
            $this->error('Recipient email is required.');
            return;
        }

        $mailer = env('MAIL_MAILER', 'log');
        $from = env('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $fromName = env('MAIL_FROM_NAME', 'Zenith Framework');

        $this->info('Sending test email...');
        $this->line("  To:      {$to}");
        $this->line("  From:    {$fromName} <{$from}>");
        $this->line("  Mailer:  {$mailer}");

        if ($mailer === 'log') {
            $this->warn('Mail driver is set to "log". Email will be written to log files.');

            $logPath = dirname(__DIR__, 3) . '/storage/logs';

            if (!is_dir($logPath)) {
                mkdir($logPath, 0755, true);
            }

            $logFile = $logPath . '/mail.log';
            $content = "[{$to}] Test Email - " . date('Y-m-d H:i:s') . "\n";
            $content .= "Subject: Test Email from Zenith Framework\n";
            $content .= "Body: This is a test email from Zenith Framework.\n";
            $content .= str_repeat('-', 60) . "\n";

            file_put_contents($logFile, $content, FILE_APPEND);

            $this->info("Test email logged to: storage/logs/mail.log");
        } elseif ($mailer === 'smtp') {
            $host = env('MAIL_HOST', '');
            $port = env('MAIL_PORT', '587');

            if (empty($host)) {
                $this->error('MAIL_HOST is not configured in .env file.');
                $this->line('Please configure your SMTP settings before sending emails.');
                return;
            }

            $this->warn("SMTP sending is not yet implemented in Zenith Framework.");
            $this->line('Configure your mail driver or use a third-party service.');
        } else {
            $this->warn("Mail driver [{$mailer}] is not configured for sending emails.");
            $this->line('Set MAIL_MAILER=smtp in your .env to send emails via SMTP.');
        }
    }
}
