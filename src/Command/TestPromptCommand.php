<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-prompt', description: 'Test different prompt lengths')]
class TestPromptCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prompts = [
            'short' => 'paracetamol',
            'medium' => 'paracetamol medicine pills',
            'long' => 'professional pharmaceutical product photo of Paracetamol medication, pill bottle on white background, studio lighting',
        ];

        foreach ($prompts as $name => $prompt) {
            $output->writeln("Testing [{$name}]: {$prompt}");

            $encoded = urlencode($prompt);
            $seed = mt_rand(1, 999999);
            $url = "https://image.pollinations.ai/prompt/{$encoded}?width=512&height=512&nologo=true&seed={$seed}";
            $output->writeln("  URL length: " . strlen($url));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Referer: https://pollinations.ai/',
            ]);

            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $status = ($code === 200 && strlen($data) > 1000) ? 'SUCCESS' : 'FAIL';
            $output->writeln("  HTTP: {$code} Type: {$type} Size: " . strlen($data) . " - {$status}");
            if ($error) $output->writeln("  Error: {$error}");
            if (strlen($data) < 100) $output->writeln("  Body: {$data}");

            if ($status === 'SUCCESS') {
                $path = dirname(__DIR__, 2) . "/public/uploads/medicaments/test_{$name}.jpg";
                file_put_contents($path, $data);
                $output->writeln("  <info>Saved to {$path}</info>");
            }

            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
