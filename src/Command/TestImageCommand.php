<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-image', description: 'Test image download APIs')]
class TestImageCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing image download APIs...');

        $tests = [
            'DiceBear' => 'https://api.dicebear.com/7.x/shapes/svg?seed=paracetamol&size=512',
            'Placehold' => 'https://placehold.co/512x512/2980b9/ffffff/png?text=Paracetamol',
            'UIAvatars' => 'https://ui-avatars.com/api/?name=Paracetamol&size=512&background=2980b9&color=fff&bold=true&format=png',
            'Robohash' => 'https://robohash.org/paracetamol?set=set4&size=512x512',
            'Pravatar' => 'https://i.pravatar.cc/512',
        ];

        foreach ($tests as $name => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $status = ($code >= 200 && $code < 400 && strlen($data) > 100) ? '<info>OK</info>' : '<error>FAIL</error>';
            $output->writeln(sprintf("  %s: HTTP=%d Size=%d Type=%s %s %s", $name, $code, strlen($data), $type, $status, $error));
        }

        return Command::SUCCESS;
    }
}
