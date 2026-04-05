<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-hf', description: 'Test HF and other free image APIs')]
class TestHfCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prompt = json_encode(['inputs' => 'pharmaceutical paracetamol medication, white background']);

        // Test 1: HF new router endpoint
        $urls = [
            'HF Router SD-XL' => 'https://router.huggingface.co/hf-inference/models/stabilityai/stable-diffusion-xl-base-1.0',
            'HF Router SD-v1-5' => 'https://router.huggingface.co/hf-inference/models/runwayml/stable-diffusion-v1-5',
            'HF Router FLUX' => 'https://router.huggingface.co/hf-inference/models/black-forest-labs/FLUX.1-dev',
        ];

        foreach ($urls as $name => $url) {
            $output->writeln("Testing {$name}...");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $prompt);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $output->writeln("  HTTP: {$httpCode} Type: {$contentType} Size: " . strlen($response));
            if ($error) $output->writeln("  cURL Error: {$error}");

            if ($httpCode === 200 && strlen($response) > 5000) {
                $path = dirname(__DIR__, 2) . '/public/uploads/medicaments/test_hf.jpg';
                file_put_contents($path, $response);
                $output->writeln("  <info>SUCCESS!</info>");
                return Command::SUCCESS;
            } elseif (strlen($response) < 500) {
                $output->writeln("  Body: " . $response);
            }
            $output->writeln('');
        }

        return Command::FAILURE;
    }
}
