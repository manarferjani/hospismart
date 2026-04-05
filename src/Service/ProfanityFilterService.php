<?php

namespace App\Service;

/**
 * Service de filtrage des mots inappropriés (français et anglais).
 * Détecte les insultes, vulgarités et propos offensants dans les messages.
 */
class ProfanityFilterService
{
    /**
     * Liste des mots/expressions interdits.
     * Chaque entrée est un pattern regex (sans délimiteurs).
     */
    private const BAD_WORDS_FR = [
        // Insultes et vulgarités courantes
        'putain', 'pute', 'merde', 'bordel', 'connard', 'connasse',
        'enculé', 'enculer', 'nique', 'niquer', 'ntm', 'ntm',
        'fdp', 'fils de pute', 'salaud', 'salope', 'salopard',
        'bâtard', 'batard', 'abruti', 'abrutie',
        'con\b', 'conne', 'couillon', 'couille',
        'foutre', 'fous le camp', 'va te faire',
        'gueule', 'ta gueule', 'ferme la', 'ferme ta',
        'crétin', 'crétine', 'cretin', 'débile',
        'idiot', 'idiote', 'imbécile', 'imbecile',
        'pétasse', 'petasse', 'pouffiasse',
        'enfoiré', 'enfoire', 'ordure',
        'bouffon', 'bouffonne', 'tocard', 'trou du cul',
        'branleur', 'branleuse', 'branlette',
        'chiotte', 'chier', 'fait chier',
        'dégueulasse', 'degueulasse', 'cul\b',
        'bite', 'couilles', 'nichons',
        'trou de cul', 'tg\b', 'vtf', 'vtff',
        'pd\b', 'pédale', 'pédé', 'gouine',
        'négro', 'negro', 'bougnoule', 'youpin',
        'arabe de merde', 'sale arabe', 'sale noir',
        'sale blanc', 'race de',
    ];

    private const BAD_WORDS_EN = [
        // Common English profanity
        'fuck', 'fucker', 'fucking', 'fucked', 'fck',
        'shit', 'shitty', 'bullshit',
        'ass\b', 'asshole', 'arsehole', 'arse',
        'bitch', 'bitches', 'son of a bitch',
        'bastard', 'damn', 'damned', 'dammit',
        'dick', 'dickhead', 'cock', 'cocksucker',
        'pussy', 'cunt', 'twat', 'wanker',
        'whore', 'slut', 'hoe\b',
        'motherfucker', 'mf\b', 'stfu', 'gtfo',
        'idiot', 'moron', 'dumbass',
        'nigger', 'nigga', 'negro',
        'faggot', 'fag\b', 'dyke',
        'wtf', 'lmao', 'piss', 'pissed off',
        'bloody hell', 'bollocks', 'bugger',
        'crap', 'crappy', 'suck my',
    ];

    /**
     * Mots médicaux/hospitaliers courants (FR) qui ne doivent JAMAIS être détectés.
     * Utilisé pour éviter les faux positifs dans un contexte médical.
     */
    private const MEDICAL_WHITELIST = [
        'sang', 'saignement', 'saigner', 'sanguin', 'sanguine',
        'retard', 'retardé', 'retardée',
        'urgence', 'urgent', 'urgente',
        'analyse', 'analyser',
        'prise en charge', 'charge',
        'consultation', 'constat', 'constaté', 'constatée',
        'injection', 'perfusion', 'transfusion',
        'organe', 'organisme',
        'cancer', 'cancéreux',
        'dépistage', 'diagnostic',
        'thérapie', 'thérapeutique',
        'mortel', 'mortalité', 'mort',
        'critique', 'état critique',
        'blessure', 'blessé', 'blessée',
        'fracture', 'infection',
        'chirurgie', 'opération',
        'hôpital', 'hospitalisation',
        'médicament', 'médecin', 'médical', 'médicale',
        'patient', 'patiente',
        'coma', 'complication',
    ];

    /**
     * Variantes leetspeak et contournements courants.
     */
    private const LEET_MAP = [
        '@' => 'a',
        '0' => 'o',
        '1' => 'i',
        '3' => 'e',
        '4' => 'a',
        '5' => 's',
        '$' => 's',
        '7' => 't',
        '!' => 'i',
    ];

    /**
     * Vérifie si un texte contient des mots inappropriés.
     *
     * @return array{clean: bool, words: string[], message: string|null}
     */
    public function check(string $text): array
    {
        $foundWords = [];

        // Normaliser le texte
        $normalized = $this->normalize($text);
        $lowerText = mb_strtolower($text);

        // Extraire les mots du texte pour vérification contextuelle
        $textWords = preg_split('/[\s,;.:!?\-\'"()\[\]]+/u', $lowerText, -1, PREG_SPLIT_NO_EMPTY);
        $normalizedWords = preg_split('/[\s,;.:!?\-\'"()\[\]]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        // Vérifier les mots français
        foreach (self::BAD_WORDS_FR as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '/iu';
            // Certains mots ont déjà \b dans la définition, gérer ça
            $pattern = str_replace('\\\\b', '\b', $pattern);
            if (preg_match($pattern, $normalized) || preg_match($pattern, $text)) {
                // Vérifier que ce n'est pas un faux positif médical
                if (!$this->isWhitelistedContext($word, $textWords, $normalizedWords)) {
                    $foundWords[] = $word;
                }
            }
        }

        // Vérifier les mots anglais
        foreach (self::BAD_WORDS_EN as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '/iu';
            $pattern = str_replace('\\\\b', '\b', $pattern);
            if (preg_match($pattern, $normalized) || preg_match($pattern, $text)) {
                if (!$this->isWhitelistedContext($word, $textWords, $normalizedWords)) {
                    $foundWords[] = $word;
                }
            }
        }

        // Dédupliquer
        $foundWords = array_unique($foundWords);

        if (empty($foundWords)) {
            return [
                'clean'   => true,
                'words'   => [],
                'message' => null,
            ];
        }

        return [
            'clean'   => false,
            'words'   => $foundWords,
            'message' => $this->buildWarningMessage(count($foundWords)),
        ];
    }

    /**
     * Normalise le texte : supprime les accents, le leetspeak, les caractères répétés.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        // Remplacer les caractères leetspeak
        $text = strtr($text, self::LEET_MAP);

        // Supprimer les caractères répétés excessifs (ex: "fuuuuck" → "fuck")
        $text = preg_replace('/(.)\1{2,}/u', '$1', $text);

        // Supprimer les points/tirets/underscores entre les lettres (ex: "f.u.c.k")
        $text = preg_replace('/(?<=\w)[.\-_*]+(?=\w)/u', '', $text);

        // Supprimer les espaces dans les mots courts (ex: "p u t a i n")
        // On normalise les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);

        // Translittérer les accents (é→e, è→e, etc.)
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        } else {
            $text = strtr($text, [
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'à' => 'a', 'â' => 'a', 'ä' => 'a',
                'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                'ô' => 'o', 'ö' => 'o',
                'î' => 'i', 'ï' => 'i',
                'ç' => 'c',
            ]);
        }

        return $text;
    }

    /**
     * Construit le message d'avertissement.
     */
    private function buildWarningMessage(int $count): string
    {
        return "🚫 **Langage inapproprié détecté**\n\n"
            . "Votre message contient des propos inappropriés. "
            . "En tant que plateforme hospitalière, nous vous demandons de maintenir un langage respectueux et professionnel.\n\n"
            . "✅ Veuillez reformuler votre message de manière courtoise pour que nous puissions vous aider au mieux.\n\n"
            . "_Merci pour votre compréhension._";
    }

    /**
     * Vérifie si le mot détecté est en réalité un terme médical autorisé (faux positif).
     */
    private function isWhitelistedContext(string $badWord, array $textWords, array $normalizedWords): bool
    {
        // Nettoyer le bad word (retirer \b)
        $cleanBad = str_replace('\b', '', mb_strtolower($badWord));

        foreach (self::MEDICAL_WHITELIST as $safeWord) {
            $safeLower = mb_strtolower($safeWord);
            // Si le mot médical contient le mauvais mot, vérifier s'il est dans le texte
            if (str_contains($safeLower, $cleanBad) || $safeLower === $cleanBad) {
                // Vérifier si le mot safe apparaît tel quel dans les mots du texte
                foreach ($textWords as $tw) {
                    if (str_contains($tw, $safeLower) || $safeLower === $tw) {
                        return true;
                    }
                }
                foreach ($normalizedWords as $nw) {
                    if (str_contains($nw, $safeLower) || $safeLower === $nw) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
