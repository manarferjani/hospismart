<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-apis', description: 'Test various AI image APIs')]
class TestApisCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apis = [
            'Pollinations (alt)' => [
                'url' => 'https://pollinations.ai/p/pharmaceutical_paracetamol?width=256&height=256',
                'method' => 'GET',
            ],
            'Stability AI demo' => [
                'url' => 'https://clipdrop-api.co/text-to-image/v1',
                'method' => 'HEAD',
            ],
            'Craiyon' => [
                'url' => 'https://api.craiyon.com/v3',
                'method' => 'HEAD',
            ],
            'Limewire' => [
                'url' => 'https://api.limewire.com/api/image/generation',
                'method' => 'HEAD',
            ],
            'DeepAI' => [
                'url' => 'https://api.deepai.org/api/text2img',
                'method' => 'HEAD',
            ],
            'Unsplash source' => [
                'url' => 'https://source.unsplash.com/512x512/?medicine+pills',
                'method' => 'GET',
            ],
            'Pexels' => [
                'url' => 'https://api.pexels.com/v1/search?query=medicine+pills&per_page=1',
                'method' => 'HEAD',
            ],
            'Pixabay' => [
                'url' => 'https://pixabay.com/api/?key=demo&q=medicine+pills&per_page=3',
                'method' => 'GET',
            ],
        ];

        foreach ($apis as $name => $config) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            if ($config['method'] === 'HEAD') {
                curl_setopt($ch, CURLOPT_NOBODY, true);
            }

            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $status = ($code >= 200 && $code < 500) ? 'REACHABLE' : 'BLOCKED';
            $output->writeln(sprintf("  %-25s HTTP=%d Type=%s Size=%d %s %s", $name, $code, $type ?: 'N/A', strlen($data), $status, $error));
        }

        return Command::SUCCESS;
    }
}
