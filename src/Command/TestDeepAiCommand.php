<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-deepai', description: 'Test DeepAI API')]
class TestDeepAiCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiKey = 'bc238535-08d6-40a4-8d38-35a7ad455a01';
        $output->writeln('Testing DeepAI...');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.deepai.org/api/text2img');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['text' => 'paracetamol medicine']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['api-key: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $output->writeln("HTTP: {$httpCode}");
        
        // Print full response cleanly
        $lines = str_split($response, 80);
        foreach ($lines as $line) {
            $output->writeln($line);
        }

        return Command::SUCCESS;
    }
}
