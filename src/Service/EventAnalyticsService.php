<?php

namespace App\Service;

use App\Repository\EvenementRepository;

class EventAnalyticsService
{
    private EvenementRepository $repo;

    public function __construct(EvenementRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Generate comprehensive AI analytics for the event module.
     */
    public function getAnalytics(): array
    {
        $events = $this->repo->findBy([], ['date_debut' => 'DESC']);

        return [
            'overview' => $this->getOverview($events),
            'typeDistribution' => $this->getTypeDistribution($events),
            'monthlyTrend' => $this->getMonthlyTrend($events),
            'budgetAnalysis' => $this->getBudgetAnalysis($events),
            'aiInsights' => $this->generateInsights($events),
            'statusBreakdown' => $this->getStatusBreakdown($events),
            'participationStats' => $this->getParticipationStats($events),
        ];
    }

    private function getOverview(array $events): array
    {
        $total = count($events);
        $now = new \DateTime();
        $upcoming = 0;
        $past = 0;
        $totalParticipants = 0;
        $totalBudget = 0;

        foreach ($events as $e) {
            if ($e->getDateDebut() && $e->getDateDebut() > $now) {
                $upcoming++;
            } else {
                $past++;
            }
            $totalParticipants += $e->getParticipants()->count();
            $totalBudget += (float)($e->getBudgetAlloue() ?? 0);
        }

        return [
            'total' => $total,
            'upcoming' => $upcoming,
            'past' => $past,
            'totalParticipants' => $totalParticipants,
            'avgParticipants' => $total > 0 ? round($totalParticipants / $total, 1) : 0,
            'totalBudget' => $totalBudget,
            'avgBudget' => $total > 0 ? round($totalBudget / $total, 2) : 0,
        ];
    }

    private function getTypeDistribution(array $events): array
    {
        $dist = [];
        foreach ($events as $e) {
            $type = $e->getTypeEvenement() ?? 'autre';
            $dist[$type] = ($dist[$type] ?? 0) + 1;
        }
        arsort($dist);
        return $dist;
    }

    private function getMonthlyTrend(array $events): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $key = $date->format('Y-m');
            $label = $this->frenchMonth((int)$date->format('n')) . ' ' . $date->format('Y');
            $months[$key] = ['label' => $label, 'count' => 0, 'budget' => 0];
        }

        foreach ($events as $e) {
            if ($e->getDateDebut()) {
                $key = $e->getDateDebut()->format('Y-m');
                if (isset($months[$key])) {
                    $months[$key]['count']++;
                    $months[$key]['budget'] += (float)($e->getBudgetAlloue() ?? 0);
                }
            }
        }

        return array_values($months);
    }

    private function getBudgetAnalysis(array $events): array
    {
        $byType = [];
        foreach ($events as $e) {
            $type = $e->getTypeEvenement() ?? 'autre';
            if (!isset($byType[$type])) {
                $byType[$type] = ['total' => 0, 'count' => 0, 'min' => PHP_FLOAT_MAX, 'max' => 0];
            }
            $b = (float)($e->getBudgetAlloue() ?? 0);
            $byType[$type]['total'] += $b;
            $byType[$type]['count']++;
            if ($b > 0) {
                $byType[$type]['min'] = min($byType[$type]['min'], $b);
                $byType[$type]['max'] = max($byType[$type]['max'], $b);
            }
        }

        foreach ($byType as &$v) {
            $v['avg'] = $v['count'] > 0 ? round($v['total'] / $v['count'], 2) : 0;
            if ($v['min'] === PHP_FLOAT_MAX) $v['min'] = 0;
        }

        return $byType;
    }

    private function getStatusBreakdown(array $events): array
    {
        $status = [];
        foreach ($events as $e) {
            $s = $e->getStatut() ?? 'inconnu';
            $status[$s] = ($status[$s] ?? 0) + 1;
        }
        return $status;
    }

    private function getParticipationStats(array $events): array
    {
        $stats = [];
        foreach ($events as $e) {
            $count = $e->getParticipants()->count();
            $stats[] = [
                'titre' => $e->getTitre(),
                'type' => $e->getTypeEvenement(),
                'participants' => $count,
                'budget' => (float)($e->getBudgetAlloue() ?? 0),
            ];
        }
        usort($stats, fn($a, $b) => $b['participants'] <=> $a['participants']);
        return array_slice($stats, 0, 5);
    }

    /**
     * AI-powered insights generator — analyzes patterns and generates recommendations
     */
    private function generateInsights(array $events): array
    {
        $insights = [];
        $total = count($events);
        if ($total === 0) {
            return [['type' => 'info', 'icon' => '📊', 'text' => 'Aucun événement enregistré. Créez votre premier événement pour activer les analyses IA.']];
        }

        // Type dominance
        $types = $this->getTypeDistribution($events);
        $topType = array_key_first($types);
        $topTypeCount = $types[$topType];
        $pct = round(($topTypeCount / $total) * 100);
        if ($pct > 50) {
            $insights[] = ['type' => 'warning', 'icon' => '📋', 'text' => "Les événements de type \"$topType\" représentent $pct% du total. Pensez à diversifier les types d'activités."];
        } else {
            $insights[] = ['type' => 'success', 'icon' => '✅', 'text' => "Bonne diversité d'événements ! Le type le plus fréquent (\"$topType\") ne représente que $pct% du total."];
        }

        // Budget analysis
        $overview = $this->getOverview($events);
        if ($overview['avgBudget'] > 0) {
            $avgBudget = number_format($overview['avgBudget'], 2, ',', ' ');
            $insights[] = ['type' => 'info', 'icon' => '💰', 'text' => "Budget moyen par événement : $avgBudget TND. Budget total alloué : " . number_format($overview['totalBudget'], 2, ',', ' ') . " TND."];
        }

        // Participation rate
        if ($overview['avgParticipants'] < 2) {
            $insights[] = ['type' => 'warning', 'icon' => '👥', 'text' => "Le taux de participation moyen est faible (" . $overview['avgParticipants'] . " participants/événement). Envisagez des actions de communication pour augmenter l'engagement."];
        } elseif ($overview['avgParticipants'] >= 5) {
            $insights[] = ['type' => 'success', 'icon' => '🎉', 'text' => "Excellent taux de participation ! Moyenne de " . $overview['avgParticipants'] . " participants par événement."];
        }

        // Upcoming events
        if ($overview['upcoming'] === 0) {
            $insights[] = ['type' => 'alert', 'icon' => '⚠️', 'text' => "Aucun événement à venir n'est planifié. Il est recommandé de planifier les prochaines activités."];
        } else {
            $insights[] = ['type' => 'info', 'icon' => '📅', 'text' => $overview['upcoming'] . " événement(s) à venir. " . $overview['past'] . " événement(s) passé(s)."];
        }

        // Status analysis
        $statuses = $this->getStatusBreakdown($events);
        $cancelled = $statuses['annulé'] ?? 0;
        if ($cancelled > 0 && ($cancelled / $total) > 0.2) {
            $cancelPct = round(($cancelled / $total) * 100);
            $insights[] = ['type' => 'alert', 'icon' => '🚫', 'text' => "$cancelPct% des événements ont été annulés. Analysez les causes d'annulation pour améliorer la planification."];
        }

        return $insights;
    }

    private function frenchMonth(int $month): string
    {
        $months = [1=>'Jan', 2=>'Fév', 3=>'Mar', 4=>'Avr', 5=>'Mai', 6=>'Juin',
                   7=>'Juil', 8=>'Août', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Déc'];
        return $months[$month] ?? '';
    }
}