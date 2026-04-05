<?php

namespace App\Service;

use App\Entity\Medicament;
use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;

class StockPredictionService
{
    private MouvementStockRepository $mouvementRepo;
    private MedicamentRepository $medicamentRepo;

    public function __construct(
        MouvementStockRepository $mouvementRepo,
        MedicamentRepository $medicamentRepo
    ) {
        $this->mouvementRepo = $mouvementRepo;
        $this->medicamentRepo = $medicamentRepo;
    }

    /**
     * Génère les prédictions pour tous les médicaments
     * @return array<int, array> Tableau de prédictions trié par urgence
     */
    public function predictAll(): array
    {
        $medicaments = $this->medicamentRepo->findAll();
        $sortiesMap = $this->mouvementRepo->getTotalSortiesParMedicament(30);

        $predictions = [];
        foreach ($medicaments as $med) {
            $predictions[] = $this->buildPrediction($med, $sortiesMap);
        }

        // Trier par jours restants (les plus urgents en premier)
        usort($predictions, function ($a, $b) {
            // Les médicaments sans données de consommation vont à la fin
            if ($a['joursRestants'] === null && $b['joursRestants'] === null) return 0;
            if ($a['joursRestants'] === null) return 1;
            if ($b['joursRestants'] === null) return -1;
            return $a['joursRestants'] <=> $b['joursRestants'];
        });

        return $predictions;
    }

    /**
     * Prédiction pour un seul médicament
     */
    public function predictForMedicament(Medicament $med): array
    {
        $sortiesMap = $this->mouvementRepo->getTotalSortiesParMedicament(30);
        return $this->buildPrediction($med, $sortiesMap);
    }

    /**
     * Construit la prédiction pour un médicament
     */
    private function buildPrediction(Medicament $med, array $sortiesMap): array
    {
        $id = $med->getId();
        $stock = $med->getQuantite();
        $seuil = $med->getSeuilAlerte();

        // Consommation des 30 derniers jours
        $totalSorties30j = $sortiesMap[$id] ?? 0;
        $consoQuotidienne = $totalSorties30j / 30;

        // Calculer la tendance (comparer 15 derniers jours vs 15 précédents)
        $tendance = $this->calculerTendance($id);

        // Prédiction
        $joursRestants = null;
        $dateRupture = null;
        $niveauRisque = 'ok';

        if ($consoQuotidienne > 0) {
            $joursRestants = (int) round($stock / $consoQuotidienne);
            $dateRupture = new \DateTime("+{$joursRestants} days");

            if ($joursRestants <= 7) {
                $niveauRisque = 'critique';
            } elseif ($joursRestants <= 14) {
                $niveauRisque = 'eleve';
            } elseif ($joursRestants <= 30) {
                $niveauRisque = 'moyen';
            }
        } elseif ($stock <= $seuil) {
            // Pas de consommation récente mais stock déjà bas
            $niveauRisque = 'moyen';
            $joursRestants = 0;
        }

        return [
            'medicament' => $med,
            'stock' => $stock,
            'seuil' => $seuil,
            'consoQuotidienne' => round($consoQuotidienne, 1),
            'totalSorties30j' => $totalSorties30j,
            'joursRestants' => $joursRestants,
            'dateRupture' => $dateRupture,
            'niveauRisque' => $niveauRisque,
            'tendance' => $tendance,
        ];
    }

    /**
     * Calcule la tendance de consommation
     * Compare les 15 derniers jours aux 15 jours d'avant
     * Retourne 'hausse', 'baisse', ou 'stable'
     */
    private function calculerTendance(int $medicamentId): string
    {
        $dailyData = $this->mouvementRepo->getDailyConsumption($medicamentId, 30);

        if (count($dailyData) < 3) {
            return 'stable'; // Pas assez de données
        }

        $mid = new \DateTime('-15 days');
        $recentTotal = 0;
        $ancienTotal = 0;

        foreach ($dailyData as $row) {
            $date = new \DateTime($row['date_jour']);
            if ($date >= $mid) {
                $recentTotal += (int) $row['total'];
            } else {
                $ancienTotal += (int) $row['total'];
            }
        }

        if ($ancienTotal == 0 && $recentTotal == 0) {
            return 'stable';
        }

        if ($ancienTotal == 0) {
            return 'hausse';
        }

        $variation = (($recentTotal - $ancienTotal) / $ancienTotal) * 100;

        if ($variation > 15) {
            return 'hausse';
        } elseif ($variation < -15) {
            return 'baisse';
        }

        return 'stable';
    }

    /**
     * Résumé rapide pour le dashboard
     */
    public function getSummary(): array
    {
        $predictions = $this->predictAll();

        $critique = 0;
        $eleve = 0;
        $moyen = 0;
        $ok = 0;

        foreach ($predictions as $p) {
            match ($p['niveauRisque']) {
                'critique' => $critique++,
                'eleve' => $eleve++,
                'moyen' => $moyen++,
                'ok' => $ok++,
                default => null,
            };
        }

        return [
            'critique' => $critique,
            'eleve' => $eleve,
            'moyen' => $moyen,
            'ok' => $ok,
            'totalAlertes' => $critique + $eleve + $moyen,
        ];
    }
}
