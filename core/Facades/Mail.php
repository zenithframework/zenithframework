<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static \Zenith\Mail\Mailer mailer(string|null $name = null)
 * @method static \Zenith\Mail\Mailer to(string $email, string $name = '')
 * @method static \Zenith\Mail\Mailer from(string $email, string $name = '')
 * @method static \Zenith\Mail\Mailer subject(string $subject)
 * @method static \Zenith\Mail\Mailer body(string $body)
 * @method static \Zenith\Mail\Mailer view(string $view, array $data = [])
 * @method static \Zenith\Mail\Mailer attach(string $file, array $options = [])
 * @method static bool send()
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay)
 *
 * @see \Zenith\Mail\Mailer
 */
class Mail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Mail\Mailer::class;
    }
}
