<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

/**
 * Create a notification class
 */
class MakeNotificationCommand extends Command
{
    protected string $name = 'make:notification';
    protected string $description = 'Create a new notification class';

    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? $this->ask('Enter notification name');
        $path = __DIR__ . '/../../../app/Notifications/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Notification {$name} already exists!");
            return;
        }

        $content = "<?php\n\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= "namespace App\\Notifications;\n\n";
        $content .= "use ZenithFramework\\Notifications\\Notification;\n\n";
        $content .= "class {$name} extends Notification\n";
        $content .= "{\n";
        $content .= "    public function via(object \$notifiable): array\n";
        $content .= "    {\n";
        $content .= "        return ['mail', 'database'];\n";
        $content .= "    }\n\n";
        $content .= "    public function toMail(object \$notifiable): object\n";
        $content .= "    {\n";
        $content .= "        // Return MailMessage instance\n";
        $content .= "        return new class {\n";
        $content .= "            public function subject(string \$s) { return \$this; }\n";
        $content .= "            public function line(string \$l) { return \$this; }\n";
        $content .= "            public function action(string \$t, string \$u) { return \$this; }\n";
        $content .= "        };\n";
        $content .= "    }\n\n";
        $content .= "    public function toArray(object \$notifiable): array\n";
        $content .= "    {\n";
        $content .= "        return [\n";
        $content .= "            // Notification data\n";
        $content .= "        ];\n";
        $content .= "    }\n";
        $content .= "}\n";

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("✅ Notification created successfully: {$name}");
        $this->line("   Path: {$path}");
        $this->line('');
    }
}
