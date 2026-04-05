<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-pollinations', description: 'Test Pollinations.ai API')]
class TestPollinationsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing Pollinations.ai...');

        $prompt = urlencode('pharmaceutical paracetamol medication white background');
        $seed = mt_rand(1, 999999);
        $url = "https://image.pollinations.ai/prompt/{$prompt}?width=256&height=256&nologo=true&seed={$seed}";

        $output->writeln("URL: {$url}");
        $output->writeln('Downloading (this may take up to 60 seconds)...');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        $output->writeln("HTTP Code: {$httpCode}");
        $output->writeln("Content-Type: {$contentType}");
        $output->writeln("Data size: " . strlen($data) . " bytes");
        $output->writeln("cURL Error: {$error}");
        $output->writeln("cURL Errno: {$errno}");

        if ($httpCode === 200 && strlen($data) > 5000 && str_contains($contentType, 'image')) {
            $output->writeln('<info>SUCCESS! Pollinations.ai is working!</info>');
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>FAILED - Pollinations.ai is not reachable</error>');
            if ($data && strlen($data) < 500) {
                $output->writeln("Response body: " . $data);
            }
            return Command::FAILURE;
        }
    }
}
