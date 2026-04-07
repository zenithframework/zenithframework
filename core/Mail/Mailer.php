<?php

declare(strict_types=1);

namespace Zen\Mail;

class Mail
{
    public string $from;
    public string $fromEmail;
    public string $to;
    public string $toEmail;
    public string $subject = '';
    public string $body = '';
    public string $altBody = '';
    public array $headers = [];
    public array $attachments = [];

    public function from(string $address, ?string $name = null): static
    {
        $this->from = $name ?? $address;
        $this->fromEmail = $address;
        return $this;
    }

    public function to(string $address, ?string $name = null): static
    {
        $this->to = $name ?? $address;
        $this->toEmail = $address;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function html(string $html): static
    {
        $this->body = $html;
        return $this;
    }

    public function text(string $text): static
    {
        $this->altBody = $text;
        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function attach(string $path, ?string $name = null): static
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path),
        ];
        return $this;
    }

    public function send(): bool
    {
        return app(Mailer::class)->send($this);
    }
}

class Mailer
{
    protected ?MailDriver $driver = null;

    public function __construct()
    {
        $this->initDriver();
    }

    protected function initDriver(): void
    {
        $config = config('mail') ?? ['default' => 'log', 'drivers' => []];
        $driverName = $config['default'] ?? 'log';

        $this->driver = match ($driverName) {
            'smtp' => new SmtpDriver($config['drivers']['smtp'] ?? []),
            'sendmail' => new SendmailDriver($config['drivers']['sendmail'] ?? []),
            default => new LogDriver(),
        };
    }

    public function send(Mail $mail): bool
    {
        return $this->driver->send($mail);
    }

    public function raw(string $to, string $subject, string $body): bool
    {
        return (new Mail())
            ->to($to)
            ->subject($subject)
            ->body($body)
            ->send();
    }
}

interface MailDriver
{
    public function send(Mail $mail): bool;
}

class LogDriver implements MailDriver
{
    public function send(Mail $mail): bool
    {
        $log = sprintf(
            "[Mail] To: %s | From: %s | Subject: %s | Body: %s",
            $mail->toEmail,
            $mail->fromEmail,
            $mail->subject,
            substr($mail->body, 0, 100)
        );

        $logger = app(\Zen\Log\Logger::class);
        $logger->info($log);

        return true;
    }
}

class SendmailDriver implements MailDriver
{
    protected string $binary = '/usr/sbin/sendmail';

    public function __construct(array $config = [])
    {
        if (isset($config['binary'])) {
            $this->binary = $config['binary'];
        }
    }

    public function send(Mail $mail): bool
    {
        $headers = $this->buildHeaders($mail);
        $to = $mail->toEmail;

        return $this->sendMail($to, $mail->subject, $mail->body, $headers);
    }

    protected function buildHeaders(Mail $mail): string
    {
        $headers = [];
        $headers[] = "From: {$mail->from} <{$mail->fromEmail}>";
        $headers[] = "Reply-To: {$mail->from} <{$mail->fromEmail}>";
        $headers[] = "X-Mailer: Zen Framework";

        if (!empty($mail->headers)) {
            foreach ($mail->headers as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
        }

        return implode("\r\n", $headers);
    }

    protected function sendMail(string $to, string $subject, string $body, string $headers): bool
    {
        return mail($to, $subject, $body, $headers);
    }
}

class SmtpDriver implements MailDriver
{
    protected array $config;
    protected $socket = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 25,
            'username' => '',
            'password' => '',
            'encryption' => null,
            'timeout' => 30,
        ], $config);
    }

    public function send(Mail $mail): bool
    {
        $this->connect();
        $this->ehlo();

        if ($this->config['encryption'] === 'tls') {
            $this->command("STARTTLS");
            stream_socket_enable_crypto($this->socket, true, 1);
            $this->ehlo();
        }

        if (!empty($this->config['username'])) {
            $this->auth();
        }

        $this->command("MAIL FROM: <{$mail->fromEmail}>");
        $this->command("RCPT TO: <{$mail->toEmail}>");
        $this->command("DATA");

        $headers = $this->buildHeaders($mail);
        $message = $headers . "\r\n\r\n" . $mail->body . "\r\n.";
        $this->writeData($message);

        $response = $this->receive();
        $this->command("QUIT");

        return str_starts_with($response, '250');
    }

    protected function connect(): void
    {
        $host = $this->config['host'];
        $port = $this->config['port'];

        $this->socket = fsockopen($host, $port, $errno, $errstr, $this->config['timeout']);
        if (!$this->socket) {
            throw new \RuntimeException("SMTP connection failed: {$errstr}");
        }

        stream_set_timeout($this->socket, $this->config['timeout']);
        $this->receive();
    }

    protected function ehlo(): void
    {
        $this->command("EHLO " . gethostname());
    }

    protected function auth(): void
    {
        $this->command("AUTH LOGIN");
        $this->command(base64_encode($this->config['username']));
        $this->command(base64_encode($this->config['password']));
    }

    protected function command(string $data): void
    {
        fwrite($this->socket, $data . "\r\n");
    }

    protected function writeData(string $data): void
    {
        fwrite($this->socket, $data . "\r\n");
    }

    protected function receive(): string
    {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    protected function buildHeaders(Mail $mail): string
    {
        $headers = [];
        $headers[] = "From: {$mail->from} <{$mail->fromEmail}>";
        $headers[] = "To: {$mail->to} <{$mail->toEmail}>";
        $headers[] = "Subject: {$mail->subject}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "X-Mailer: Zen Framework";

        if (!empty($mail->headers)) {
            foreach ($mail->headers as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
        }

        return implode("\r\n", $headers);
    }

    public function __destruct()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
    }
}