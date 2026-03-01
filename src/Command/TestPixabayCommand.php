<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-pixabay', description: 'Test Pixabay free API')]
class TestPixabayCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Pixabay has a free API - 500 requests/day
        // Key obtained from pixabay.com/api/docs/
        // Test with the publicly documented demo key first
        $output->writeln('Testing various free image search APIs...');

        // Try direct image search from Wikipedia Commons
        $output->writeln("\n--- Wikipedia Commons API ---");
        $url = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=paracetamol+medicine&gsrnamespace=6&gsrlimit=3&prop=imageinfo&iiprop=url|size|mime&iiurlwidth=512&format=json';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HospiSmart/1.0 (educational project)');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $output->writeln("  HTTP: {$httpCode} Size: " . strlen($response));

        if ($httpCode === 200) {
            $json = json_decode($response, true);
            if (isset($json['query']['pages'])) {
                foreach ($json['query']['pages'] as $page) {
                    if (isset($page['imageinfo'][0])) {
                        $info = $page['imageinfo'][0];
                        $output->writeln("  Found: " . ($info['thumburl'] ?? $info['url']));
                        $output->writeln("  Size: {$info['width']}x{$info['height']} Mime: {$info['mime']}");

                        // Try downloading the thumbnail
                        if (isset($info['thumburl'])) {
                            $imgCh = curl_init($info['thumburl']);
                            curl_setopt($imgCh, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($imgCh, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($imgCh, CURLOPT_TIMEOUT, 30);
                            curl_setopt($imgCh, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($imgCh, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($imgCh, CURLOPT_USERAGENT, 'HospiSmart/1.0');
                            $imgData = curl_exec($imgCh);
                            $imgCode = curl_getinfo($imgCh, CURLINFO_HTTP_CODE);
                            curl_close($imgCh);

                            if ($imgCode === 200 && strlen($imgData) > 1000) {
                                $path = dirname(__DIR__, 2) . '/public/uploads/medicaments/test_wiki.jpg';
                                file_put_contents($path, $imgData);
                                $output->writeln("  <info>SUCCESS! Image saved (" . strlen($imgData) . " bytes)</info>");
                                return Command::SUCCESS;
                            }
                            $output->writeln("  Download: HTTP={$imgCode} Size=" . strlen($imgData));
                        }
                    }
                }
            } else {
                $output->writeln("  No results found");
            }
        }

        return Command::FAILURE;
    }
}
