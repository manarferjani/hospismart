<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-final', description: 'Test remaining AI APIs')]
class TestFinalCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Test 1: Together.ai reachability
        $output->writeln('Test 1: Together.ai...');
        $this->testUrl($output, 'https://api.together.xyz/v1/images/generations');

        // Test 2: Stability AI
        $output->writeln('Test 2: Stability AI...');
        $this->testUrl($output, 'https://api.stability.ai/v2beta/stable-image/generate/core');

        // Test 3: Segmind
        $output->writeln('Test 3: Segmind...');
        $this->testUrl($output, 'https://api.segmind.com/v1/sdxl1.0-txt2img');

        // Test 4: Leonardo.ai
        $output->writeln('Test 4: Leonardo.ai...');
        $this->testUrl($output, 'https://cloud.leonardo.ai/api/rest/v1/generations');

        // Test 5: Freepik AI
        $output->writeln('Test 5: Freepik AI...');
        $this->testUrl($output, 'https://api.freepik.com/v1/ai/text-to-image');

        // Test 6: Try Pollinations with different User Agent
        $output->writeln('Test 6: Pollinations (Chrome UA)...');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://image.pollinations.ai/prompt/test?width=256&height=256&nologo=true');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
        curl_close($ch);
        $output->writeln("  HTTP: {$code} Type: {$type} Size: " . strlen($data));
        if ($code === 200 && strlen($data) > 5000) {
            $path = dirname(__DIR__, 2) . '/public/uploads/medicaments/test_pollinations.png';
            file_put_contents($path, $data);
            $output->writeln("  <info>SUCCESS!</info>");
        } elseif (strlen($data) < 200) {
            $output->writeln("  Body: {$data}");
        }

        return Command::SUCCESS;
    }

    private function testUrl(OutputInterface $output, string $url): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $status = $code > 0 ? 'REACHABLE' : 'BLOCKED';
        $output->writeln("  HTTP: {$code} - {$status} {$error}");
    }
}
