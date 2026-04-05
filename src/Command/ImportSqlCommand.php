<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-sql',
    description: 'Importe un fichier SQL dans la base de données configurée',
)]
final class ImportSqlCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin vers le fichier SQL à importer (relatif à la racine du projet ou chemin absolu)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        if (!file_exists($file)) {
            $io->error(sprintf('Fichier SQL introuvable : %s', $file));
            return Command::FAILURE;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            $io->error('Impossible de lire le fichier SQL.');
            return Command::FAILURE;
        }

        $io->title('Import SQL');

        $this->connection->beginTransaction();
        try {
            // Split statements by semicolon followed by newline. This is simple but covers most dumps.
            $statements = preg_split('/;\s*\r?\n/', $sql);

            $count = 0;
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '' || strpos($statement, '--') === 0) {
                    continue;
                }

                // Some dumps include DELIMITER or comments — skip unsupported lines
                if (stripos($statement, 'DELIMITER') === 0) {
                    continue;
                }

                $this->connection->executeStatement($statement);
                $count++;
            }

            $this->connection->commit();
            $io->success(sprintf('Import terminé : %d statements exécutés.', $count));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $io->error('Erreur durant l\'import SQL : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
