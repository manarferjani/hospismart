<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-craiyon', description: 'Test Craiyon AI image generation')]
class TestCraiyonCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Test 1: Craiyon (formerly DALL-E mini) - truly free AI
        $output->writeln('Test 1: Craiyon v3 API...');
        $payload = json_encode([
            'prompt' => 'pharmaceutical paracetamol medication white background',
            'version' => 'c4ue22fb7kb6wlac',
            'token' => null
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.craiyon.com/v3');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $output->writeln("  HTTP: {$code} Size: " . strlen($response));
        if ($error) $output->writeln("  Error: {$error}");

        if ($code === 200 && $response) {
            $json = json_decode($response, true);
            if ($json) {
                $output->writeln("  Keys: " . implode(', ', array_keys($json)));
                if (isset($json['images']) && count($json['images']) > 0) {
                    $output->writeln("  Found " . count($json['images']) . " images!");
                    // Craiyon returns base64 images
                    $imgData = base64_decode($json['images'][0]);
                    if ($imgData) {
                        $path = dirname(__DIR__, 2) . '/public/uploads/medicaments/test_craiyon.jpg';
                        file_put_contents($path, $imgData);
                        $output->writeln("  <info>SUCCESS! Saved (" . strlen($imgData) . " bytes)</info>");
                    }
                }
            } else {
                $output->writeln("  Response: " . substr($response, 0, 300));
            }
        }

        // Test 2: Dezgo free API
        $output->writeln('');
        $output->writeln('Test 2: Dezgo free API...');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.dezgo.com/text2image');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'prompt' => 'pharmaceutical paracetamol medication white background',
            'width' => 512,
            'height' => 512,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response2 = curl_exec($ch);
        $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type2 = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $output->writeln("  HTTP: {$code2} Type: {$type2} Size: " . strlen($response2));
        if ($code2 === 200 && strlen($response2) > 5000) {
            $path = dirname(__DIR__, 2) . '/public/uploads/medicaments/test_dezgo.png';
            file_put_contents($path, $response2);
            $output->writeln("  <info>SUCCESS!</info>");
        }

        // Test 3: Prodia free API
        $output->writeln('');
        $output->writeln('Test 3: Prodia API...');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.prodia.com/v1/sd/generate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'prompt' => 'pharmaceutical paracetamol medication white background',
            'model' => 'v1-5-pruned-emaonly.safetensors [d7049739]',
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response3 = curl_exec($ch);
        $code3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $output->writeln("  HTTP: {$code3} Size: " . strlen($response3));
        if ($response3 && strlen($response3) < 500) {
            $output->writeln("  Body: {$response3}");
        }

        return Command::SUCCESS;
    }
}
