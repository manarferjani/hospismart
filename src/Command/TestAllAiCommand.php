<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-all-ai', description: 'Test ALL free AI image APIs')]
class TestAllAiCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Testing ALL free AI image generation APIs...</info>');
        $output->writeln('');

        $prompt = 'pharmaceutical paracetamol medication white background studio lighting';
        $jsonPrompt = json_encode(['inputs' => $prompt]);
        $jsonPrompt2 = json_encode(['prompt' => $prompt]);

        $tests = [
            // Pollinations alternatives
            ['Pollinations v1', 'GET', 'https://image.pollinations.ai/prompt/' . urlencode($prompt) . '?width=256&height=256&nologo=true&seed=42'],
            
            // Together.ai (free tier)
            ['Together.ai', 'HEAD', 'https://api.together.xyz/v1/images/generations'],
            
            // Replicate
            ['Replicate', 'HEAD', 'https://api.replicate.com/v1/predictions'],

            // Stability AI
            ['StabilityAI', 'HEAD', 'https://api.stability.ai/v1/generation/stable-diffusion-v1-6/text-to-image'],

            // Cloudflare AI
            ['Cloudflare AI', 'HEAD', 'https://api.cloudflare.com/client/v4/accounts'],

            // Craiyon (v3 POST)
            ['Craiyon v3', 'POST', 'https://api.craiyon.com/v3', json_encode(['prompt' => $prompt, 'version' => 'c4ue22fb7kb6wlac', 'token' => null])],

            // ArtBreeder
            ['ArtBreeder', 'HEAD', 'https://www.artbreeder.com'],

            // Prodia free API
            ['Prodia', 'HEAD', 'https://api.prodia.com/v1/sd/generate'],

            // StarryAI
            ['StarryAI', 'HEAD', 'https://api.starryai.com'],

            // Leonardo.ai
            ['Leonardo', 'HEAD', 'https://cloud.leonardo.ai/api/rest/v1/generations'],

            // Segmind
            ['Segmind', 'HEAD', 'https://api.segmind.com/v1/sdxl1.0-txt2img'],

            // GetImg.ai
            ['GetImg', 'HEAD', 'https://api.getimg.ai/v1/stable-diffusion/text-to-image'],

            // Dezgo free
            ['Dezgo', 'GET', 'https://api.dezgo.com/text2image?prompt=' . urlencode($prompt) . '&width=256&height=256'],

            // Limewire
            ['Limewire', 'HEAD', 'https://api.limewire.com/api/image/generation'],
        ];

        foreach ($tests as $test) {
            $name = $test[0];
            $method = $test[1];
            $url = $test[2];
            $body = $test[3] ?? null;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

            if ($method === 'HEAD') {
                curl_setopt($ch, CURLOPT_NOBODY, true);
            } elseif ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                }
            }

            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            // Determine if reachable (any HTTP response = reachable, 0 = blocked)
            if ($code === 0) {
                $status = 'BLOCKED';
            } elseif ($code >= 200 && $code < 300 && strlen($data) > 5000) {
                $status = 'WORKS!!!';
            } elseif ($code >= 200 && $code < 500) {
                $status = 'REACHABLE';
            } else {
                $status = 'ERROR';
            }

            $output->writeln(sprintf("  %-20s HTTP=%-3d Size=%-8d %s", $name, $code, strlen($data), $status));
        }

        return Command::SUCCESS;
    }
}
