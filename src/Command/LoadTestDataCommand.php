<?php

namespace App\Command;

use App\Entity\Medicament;
use App\Entity\MouvementStock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-test-data',
    description: 'Charge des données de test réalistes pour la démo',
)]
final class LoadTestDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Chargement des données de test');

        // Données réalistes de médicaments
        $medicaments = [
            [
                'nom' => 'Paracétamol 500mg',
                'quantite' => 450,
                'seuil_alerte' => 100,
                'prix_unitaire' => 2.50,
                'date_peremption' => '2026-12-31',
            ],
            [
                'nom' => 'Ibuprofène 400mg',
                'quantite' => 320,
                'seuil_alerte' => 80,
                'prix_unitaire' => 3.20,
                'date_peremption' => '2026-11-15',
            ],
            [
                'nom' => 'Amoxicilline 1g',
                'quantite' => 150,
                'seuil_alerte' => 50,
                'prix_unitaire' => 5.80,
                'date_peremption' => '2026-09-20',
            ],
            [
                'nom' => 'Azithromycine 500mg',
                'quantite' => 75,
                'seuil_alerte' => 50,
                'prix_unitaire' => 8.50,
                'date_peremption' => '2026-08-10',
            ],
            [
                'nom' => 'Loratadine 10mg',
                'quantite' => 280,
                'seuil_alerte' => 100,
                'prix_unitaire' => 1.90,
                'date_peremption' => '2027-03-25',
            ],
            [
                'nom' => 'Oméprazole 20mg',
                'quantite' => 200,
                'seuil_alerte' => 60,
                'prix_unitaire' => 4.50,
                'date_peremption' => '2026-10-30',
            ],
            [
                'nom' => 'Métoprolol 50mg',
                'quantite' => 120,
                'seuil_alerte' => 40,
                'prix_unitaire' => 6.20,
                'date_peremption' => '2026-12-01',
            ],
            [
                'nom' => 'Simvastatine 20mg',
                'quantite' => 95,
                'seuil_alerte' => 50,
                'prix_unitaire' => 7.80,
                'date_peremption' => '2026-09-15',
            ],
            [
                'nom' => 'Fluoxétine 20mg',
                'quantite' => 160,
                'seuil_alerte' => 50,
                'prix_unitaire' => 9.00,
                'date_peremption' => '2026-11-20',
            ],
            [
                'nom' => 'Lisinopril 10mg',
                'quantite' => 220,
                'seuil_alerte' => 70,
                'prix_unitaire' => 5.40,
                'date_peremption' => '2026-10-15',
            ],
        ];

        // Vider les tables existantes
        $this->em->createQuery('DELETE FROM App\Entity\MouvementStock')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Medicament')->execute();

        // Créer les médicaments
        $createdMedicaments = [];
        foreach ($medicaments as $data) {
            $med = new Medicament();
            $med->setNom($data['nom']);
            $med->setQuantite($data['quantite']);
            $med->setSeuilAlerte($data['seuil_alerte']);
            $med->setPrixUnitaire($data['prix_unitaire']);
            $med->setDatePeremption(new \DateTime($data['date_peremption']));
            
            $this->em->persist($med);
            $createdMedicaments[] = $med;
        }
        $this->em->flush();

        $io->success(count($medicaments) . ' médicaments créés');

        // Créer des mouvements de stock réalistes
        $mouvements = [
            ['med' => 0, 'type' => 'ENTREE', 'qty' => 100, 'date' => '-5 days', 'comment' => 'Réappro Pharmacie Centrale'],
            ['med' => 0, 'type' => 'SORTIE', 'qty' => 50, 'date' => '-2 days', 'comment' => 'Consommation Ward A'],
            ['med' => 1, 'type' => 'ENTREE', 'qty' => 150, 'date' => '-7 days', 'comment' => 'Commande fournisseur'],
            ['med' => 1, 'type' => 'SORTIE', 'qty' => 80, 'date' => '-1 day', 'comment' => 'Dispensation patients ICU'],
            ['med' => 2, 'type' => 'ENTREE', 'qty' => 75, 'date' => '-10 days', 'comment' => 'Achat d\'urgence'],
            ['med' => 2, 'type' => 'SORTIE', 'qty' => 40, 'date' => '-3 days', 'comment' => 'Traitement infectieux'],
            ['med' => 3, 'type' => 'SORTIE', 'qty' => 30, 'date' => '-1 day', 'comment' => 'Prescription médicale'],
            ['med' => 4, 'type' => 'ENTREE', 'qty' => 200, 'date' => '-6 days', 'comment' => 'Stock saisonnier'],
            ['med' => 5, 'type' => 'ENTREE', 'qty' => 100, 'date' => '-4 days', 'comment' => 'Réappro'],
            ['med' => 5, 'type' => 'SORTIE', 'qty' => 45, 'date' => '-2 days', 'comment' => 'Patients RDV'],
            ['med' => 6, 'type' => 'SORTIE', 'qty' => 20, 'date' => '-5 days', 'comment' => 'Cardiologie Ward'],
            ['med' => 7, 'type' => 'ENTREE', 'qty' => 50, 'date' => '-8 days', 'comment' => 'Commande pharmacien'],
            ['med' => 8, 'type' => 'ENTREE', 'qty' => 80, 'date' => '-3 days', 'comment' => 'Psych dept'],
            ['med' => 9, 'type' => 'SORTIE', 'qty' => 60, 'date' => '-2 days', 'comment' => 'Hypertension patients'],
        ];

        foreach ($mouvements as $data) {
            $mvt = new MouvementStock();
            $mvt->setMedicament($createdMedicaments[$data['med']]);
            $mvt->setType($data['type']);
            $mvt->setQuantite($data['qty']);
            $mvt->setCommentaire($data['comment']);
            $mvt->setDateMouvement(new \DateTime($data['date']));
            
            $this->em->persist($mvt);
        }
        $this->em->flush();

        $io->success(count($mouvements) . ' mouvements de stock créés');
        $io->info('✅ Données de test chargées avec succès ! Rendez-vous sur /dashboard');

        return Command::SUCCESS;
    }
}
