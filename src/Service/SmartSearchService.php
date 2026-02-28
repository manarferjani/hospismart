<?php

namespace App\Service;

use App\Repository\EvenementRepository;

class SmartSearchService
{
    private EvenementRepository $repo;

    public function __construct(EvenementRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Smart search with fuzzy matching, French stemming, and scoring.
     */
    public function search(string $query): array
    {
        if (empty(trim($query))) {
            return [];
        }

        // Get all events
        $allEvents = $this->repo->findBy([], ['date_debut' => 'DESC']);
        $query = mb_strtolower(trim($query));
        $queryTokens = $this->tokenize($query);
        $stemmedTokens = array_map([$this, 'stemFrench'], $queryTokens);

        $results = [];

        foreach ($allEvents as $event) {
            $score = $this->scoreEvent($event, $query, $queryTokens, $stemmedTokens);
            if ($score > 0) {
                $results[] = [
                    'event' => $event,
                    'score' => $score,
                    'relevance' => min(100, (int)($score * 20)),
                ];
            }
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, 10);
    }

    private function scoreEvent($event, string $query, array $tokens, array $stems): float
    {
        $score = 0.0;

        // Exact match in title (highest weight)
        $titre = mb_strtolower($event->getTitre() ?? '');
        if (str_contains($titre, $query)) {
            $score += 5.0;
        }

        // Exact match in description
        $desc = mb_strtolower($event->getDescription() ?? '');
        if (str_contains($desc, $query)) {
            $score += 2.0;
        }

        // Exact match in lieu
        $lieu = mb_strtolower($event->getLieu() ?? '');
        if (str_contains($lieu, $query)) {
            $score += 3.0;
        }

        // Exact match in type
        $type = mb_strtolower($event->getTypeEvenement() ?? '');
        if (str_contains($type, $query)) {
            $score += 3.0;
        }

        // Token-based matching
        $titleTokens = $this->tokenize($titre);
        $descTokens = $this->tokenize($desc);
        $lieuTokens = $this->tokenize($lieu);
        $allTokens = array_merge($titleTokens, $descTokens, $lieuTokens, [$type]);
        $allStems = array_map([$this, 'stemFrench'], $allTokens);

        foreach ($tokens as $i => $token) {
            $stem = $stems[$i] ?? $token;

            // Direct token match
            foreach ($titleTokens as $tt) {
                if ($tt === $token) $score += 2.0;
                elseif (str_starts_with($tt, $token) || str_starts_with($token, $tt)) $score += 1.0;
            }

            // Stem match (French stemming)
            foreach ($allStems as $s) {
                if ($s === $stem && strlen($stem) >= 3) {
                    $score += 1.5;
                }
            }

            // Fuzzy match (Levenshtein distance)
            foreach ($allTokens as $eventToken) {
                if (strlen($eventToken) >= 3 && strlen($token) >= 3) {
                    $distance = levenshtein($token, $eventToken);
                    $maxLen = max(strlen($token), strlen($eventToken));
                    if ($distance <= 2 && $distance > 0) {
                        $score += (1.0 - ($distance / $maxLen)) * 1.5;
                    }
                }
            }
        }

        // Soundex matching for typos
        foreach ($tokens as $token) {
            if (strlen($token) >= 3) {
                $querySoundex = soundex($token);
                foreach ($titleTokens as $tt) {
                    if (strlen($tt) >= 3 && soundex($tt) === $querySoundex) {
                        $score += 0.8;
                    }
                }
            }
        }

        return $score;
    }

    private function tokenize(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        // Remove French stop words
        $stopWords = ['le','la','les','de','du','des','un','une','et','en','à','au','aux',
                       'ce','cet','cette','ces','pour','par','sur','dans','avec','est','sont',
                       'que','qui','ne','pas','se','son','sa','ses','nos','vos','leur','leurs'];
        return array_values(array_filter($tokens, fn($t) => !in_array($t, $stopWords) && strlen($t) >= 2));
    }

    /**
     * Simple French stemmer - removes common suffixes
     */
    private function stemFrench(string $word): string
    {
        $word = mb_strtolower($word);
        if (mb_strlen($word) <= 3) return $word;

        // Remove common French suffixes
        $suffixes = ['ement', 'ement', 'ation', 'tion', 'sion', 'ment', 'ance', 'ence',
                     'eur', 'euse', 'eux', 'ive', 'isse', 'iste', 'able', 'ible',
                     'aux', 'als', 'ées', 'és', 'ent', 'ant', 'ons', 'ions',
                     'er', 'ir', 're', 'es', 'ée', 'é', 's'];

        foreach ($suffixes as $suffix) {
            if (mb_strlen($word) - mb_strlen($suffix) >= 3 && str_ends_with($word, $suffix)) {
                return mb_substr($word, 0, mb_strlen($word) - mb_strlen($suffix));
            }
        }

        return $word;
    }
}