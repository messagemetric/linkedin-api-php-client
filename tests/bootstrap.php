<?php

$pathToDotEnvFile = dirname(__DIR__);
if (file_exists($pathToDotEnvFile . '/.env')) {
    Dotenv\Dotenv::createUnsafeImmutable($pathToDotEnvFile)->load();
} elseif (empty(getenv('LINKEDIN_CLIENT_ID')) || empty(getenv('LINKEDIN_CLIENT_SECRET'))) {
    echo "Create .env file with credentials or setup environment variables LINKEDIN_CLIENT_ID & LINKEDIN_CLIENT_SECRET to make tests pass.";
}
