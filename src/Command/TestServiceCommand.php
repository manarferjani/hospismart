<?php

namespace App\Command;

use App\Service\AiImageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-service', description: 'Test actual AiImageService')]
class TestServiceCommand extends Command
{
    private AiImageService $aiImageService;

    public function __construct(AiImageService $aiImageService)
    {
        parent::__construct();
        $this->aiImageService = $aiImageService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing AiImageService::generateImage("Paracetamol")...');
        $output->writeln('This may take up to 3 minutes...');

        $filename = $this->aiImageService->generateImage('Paracetamol');

        if ($filename) {
            $output->writeln("<info>SUCCESS! Image saved as: {$filename}</info>");
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>FAILED - check error logs above</error>');
            return Command::FAILURE;
        }
    }
}
