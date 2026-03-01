<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Teste l\'envoi d\'email via la configuration actuelle.',
)]
class TestEmailCommand extends Command
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Tentative d\'envoi d\'email...');

        $email = (new Email())
            ->from('arfaouimahmoud62@gmail.com')
            ->to('arfaouimahmoud62@gmail.com')
            ->subject('Test Email Hospismart')
            ->text('Ceci est un test pour vérifier la configuration SMTP.');

        try {
            $this->mailer->send($email);
            $output->writeln('<info>Email envoyé avec succès ! Vérifiez votre boîte de réception.</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur lors de l\'envoi : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
