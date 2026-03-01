<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-hf-image', description: 'Test Hugging Face Inference API for image generation')]
class TestHfImageCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiToken = $_ENV['HF_API_TOKEN'] ?? $_SERVER['HF_API_TOKEN'] ?? null;
        if (!$apiToken) {
            $output->writeln('<error>HF_API_TOKEN is not configured in .env.local</error>');
            return Command::FAILURE;
        }

        $output->writeln('Testing Hugging Face Inference API...');
        $output->writeln('Token: ' . substr($apiToken, 0, 10) . '...');
        $output->writeln('');

        $prompt = 'professional pharmaceutical product photo of paracetamol medication, pill bottle on clean white background';
        $payload = json_encode(['inputs' => $prompt], JSON_UNESCAPED_UNICODE);

        $models = [
            'stabilityai/stable-diffusion-xl-base-1.0',
            'runwayml/stable-diffusion-v1-5',
        ];

        foreach ($models as $model) {
            $output->writeln("Testing model: {$model}...");
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://router.huggingface.co/hf-inference/models/' . $model,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 180,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $output->writeln("  HTTP Code: {$httpCode}");
            $output->writeln("  Content-Type: {$contentType}");
            $output->writeln("  Response Size: " . strlen((string) $response) . " bytes");
            
            if ($error) {
                $output->writeln("  <error>cURL Error: {$error}</error>");
            }

            if ($httpCode === 503) {
                $output->writeln('  <comment>Model is loading. Trying next model...</comment>');
                if (strlen((string) $response) < 1000) {
                    $output->writeln('  Response: ' . $response);
                }
                $output->writeln('');
                continue;
            }

            if ($httpCode !== 200) {
                $output->writeln("  <error>Request failed!</error>");
                if (strlen((string) $response) < 2000) {
                    $output->writeln('  Response: ' . $response);
                } else {
                    $output->writeln('  Response (first 500 chars): ' . substr((string) $response, 0, 500));
                }
                $output->writeln('');
                continue;
            }

            if ($contentType && str_contains($contentType, 'image')) {
                $output->writeln("  <info>SUCCESS! Got an image from {$model}!</info>");
                $testPath = dirname(__DIR__, 2) . '/public/uploads/medicaments/test_hf_image.jpg';
                if (file_put_contents($testPath, $response)) {
                    $output->writeln("  Image saved to: {$testPath}");
                    return Command::SUCCESS;
                } else {
                    $output->writeln('  <error>Failed to save image file</error>');
                    return Command::FAILURE;
                }
            } else {
                $output->writeln("  <error>Response is not an image!</error>");
                $output->writeln('  Response: ' . substr((string) $response, 0, 1000));
                $output->writeln('');
            }
        }

        $output->writeln('<error>All models failed!</error>');
        return Command::FAILURE;
    }
}
