<?php
// Test rapide pour vérifier si la clé API est chargée
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env');

$apiKey = $_ENV['GEMINI_API_KEY'] ?? 'NOT_FOUND';
$masked = strlen($apiKey) > 10 ? substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) : $apiKey;

header('Content-Type: application/json');
echo json_encode([
    'api_key_loaded' => !empty($apiKey) && $apiKey !== 'NOT_FOUND',
    'api_key_masked' => $masked,
    'env_file_exists' => file_exists(dirname(__DIR__).'/.env.local'),
    'cwd' => getcwd(),
]);
