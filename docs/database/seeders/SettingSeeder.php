<?php

declare(strict_types=1);

use App\Models\Setting;

class SettingSeeder
{
    public function run(): void
    {
        // Site settings
        Setting::create([
            'key' => 'site_name',
            'value' => 'Zenith Framework Documentation',
            'type' => 'string',
        ]);

        Setting::create([
            'key' => 'site_description',
            'value' => 'Learn Zenith Framework from basics to advanced topics',
            'type' => 'string',
        ]);

        Setting::create([
            'key' => 'site_logo',
            'value' => '/images/logo.svg',
            'type' => 'string',
        ]);

        Setting::create([
            'key' => 'contact_email',
            'value' => 'hello@zenithframework.com',
            'type' => 'string',
        ]);

        Setting::create([
            'key' => 'enable_registration',
            'value' => '1',
            'type' => 'boolean',
        ]);

        Setting::create([
            'key' => 'default_role',
            'value' => 'student',
            'type' => 'string',
        ]);

        Setting::create([
            'key' => 'docs_version',
            'value' => '1.0',
            'type' => 'string',
        ]);

        Setting::create([
            'key' => 'social_links',
            'value' => json_encode([
                'github' => 'https://github.com/zenithframework',
                'twitter' => 'https://twitter.com/zenithframework',
                'discord' => 'https://discord.gg/zenithframework',
            ]),
            'type' => 'json',
        ]);
    }
}
