<?php

declare(strict_types=1);

class DatabaseSeeder
{
    public function run(): void
    {
        (new UserSeeder)->run();
        (new CategorySeeder)->run();
        (new DocumentSeeder)->run();
        (new CourseSeeder)->run();
        (new SettingSeeder)->run();
    }
}
