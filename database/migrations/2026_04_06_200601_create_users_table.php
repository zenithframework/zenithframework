<?php

declare(strict_types=1);

return new class {
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email_verified_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $qb = new \Zen\Database\QueryBuilder();
        $qb->raw($sql);
    }

    public function down(): void
    {
        $qb = new \Zen\Database\QueryBuilder();
        $qb->raw("DROP TABLE IF EXISTS users");
    }
};
