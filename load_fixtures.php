<?php
// load_fixtures.php — Script complet pour peupler TOUTES les tables

require __DIR__ . '/vendor/autoload.php';
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\User;
use App\Entity\Service;
use App\Entity\Categorie;
use App\Entity\Medicament;
use App\Entity\RendezVous;
use App\Entity\Disponibilite;
use App\Entity\Evenement;
use App\Entity\MouvementStock;
use App\Entity\Consultation;
use App\Entity\Diagnostic;
use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\Equipement;
use App\Entity\Campagne;
use App\Enum\ConsultationStatus;

(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
echo "🚀 Kernel OK\n";

try {
    $em = $kernel->getContainer()->get('doctrine')->getManager();

    // ============================
    // Helper
    // ============================
    function findOrCreate($em, string $class, array $criteria, callable $factory) {
        $existing = $em->getRepository($class)->findOneBy($criteria);
        if ($existing) return $existing;
        $entity = $factory();
        $em->persist($entity);
        return $entity;
    }

    // ============================
    // 1. SERVICES
    // ============================
    echo "📦 Services...\n";
    $servicesData = [
        'Cardiologie'       => 'Maladies du cœur et des vaisseaux',
        'Neurologie'        => 'Traitement du système nerveux',
        'Urgences'          => 'Service des urgences 24h/24',
        'Pédiatrie'         => 'Soins des enfants et nourrissons',
        'Dermatologie'      => 'Maladies de la peau',
        'Radiologie'        => 'Imagerie médicale (IRM, Scanner, Radio)',
        'Chirurgie'         => 'Interventions chirurgicales générales et spécialisées',
        'Psychiatrie'       => 'Santé mentale et troubles psychiatriques',
        'Ophtalmologie'     => 'Maladies et chirurgie des yeux',
        'Gynécologie'       => 'Santé de la femme et obstétrique',
        'Orthopédie'        => 'Traitement des os et articulations',
    ];
    $services = [];
    foreach ($servicesData as $nom => $desc) {
        $services[$nom] = findOrCreate($em, Service::class, ['nom' => $nom], function() use ($nom, $desc) {
            $s = new Service(); $s->setNom($nom); $s->setDescription($desc); return $s;
        });
    }

    // ============================
    // 2. UTILISATEURS (1 Admin + Médecins + Patients)
    // ============================
    echo "👤 Utilisateurs...\n";
    function makeUser($em, $email, $pass, $role, $nom, $prenom, $type, $extra = []) {
        return findOrCreate($em, User::class, ['email' => $email], function() use ($em, $email, $pass, $role, $nom, $prenom, $type, $extra) {
            $u = new User();
            $u->setEmail($email)->setRoles([$role])->setNom($nom)->setPrenom($prenom)
              ->setPassword(password_hash($pass, PASSWORD_BCRYPT))->setType($type);
            if (isset($extra['service']))    $u->setServiceEntity($extra['service']);
            if (isset($extra['specialite'])) $u->setSpecialite($extra['specialite']);
            if (isset($extra['matricule']))  $u->setMatricule($extra['matricule']);
            if (isset($extra['genre']))      $u->setGenre($extra['genre']);
            if (isset($extra['telephone']))  $u->setTelephone($extra['telephone']);
            if (isset($extra['adresse']))    $u->setAdresse($extra['adresse']);
            return $u;
        });
    }

    $admin = makeUser($em, 'admin@hospismart.com', 'admin123', 'ROLE_ADMIN', 'Admin', 'System', 'ADMIN');

    // Liste complète des médecins par spécialité
    $medecinsData = [
        // Cardiologie
        ['dr.house@hospismart.com', 'house123', 'House', 'Gregory', 'Neurologie', 'Diagnostiqueur', 'MED-001'],
        ['dr.yang@hospismart.com', 'yang123', 'Yang', 'Cristina', 'Cardiologie', 'Chirurgie Cardiaque', 'MED-002'],
        ['dr.burke@hospismart.com', 'burke123', 'Burke', 'Preston', 'Cardiologie', 'Cardiologue', 'MED-003'],
        
        // Urgences
        ['dr.ross@hospismart.com', 'ross123', 'Ross', 'Doug', 'Urgences', 'Urgentiste Pédiatrique', 'MED-004'],
        ['dr.greene@hospismart.com', 'greene123', 'Greene', 'Mark', 'Urgences', 'Médecine d\'urgence', 'MED-005'],
        
        // Pédiatrie
        ['dr.karev@hospismart.com', 'karev123', 'Karev', 'Alex', 'Pédiatrie', 'Chirurgie Pédiatrique', 'MED-006'],
        ['dr.robbins@hospismart.com', 'robbins123', 'Robbins', 'Arizona', 'Pédiatrie', 'Pédiatrie Générale', 'MED-007'],
        
        // Dermatologie
        ['dr.sloan@hospismart.com', 'sloan123', 'Sloan', 'Mark', 'Dermatologie', 'Chirurgie Plastique', 'MED-008'],
        ['dr.bailey@hospismart.com', 'bailey123', 'Bailey', 'Miranda', 'Chirurgie', 'Chirurgie Générale', 'MED-009'],
        
        // Neurologie / Neurochirurgie
        ['dr.shepherd@hospismart.com', 'shepherd123', 'Shepherd', 'Derek', 'Neurologie', 'Neurochirurgie', 'MED-010'],
        ['dr.grey@hospismart.com', 'grey123', 'Grey', 'Meredith', 'Chirurgie', 'Chirurgie Générale', 'MED-011'],
        
        // Radiologie
        ['dr.mccoy@hospismart.com', 'mccoy123', 'McCoy', 'Leonard', 'Radiologie', 'Radiologue', 'MED-012'],
        
        // Psychiatrie
        ['dr.lecter@hospismart.com', 'lecter123', 'Lecter', 'Hannibal', 'Psychiatrie', 'Psychiatrie Légale', 'MED-013'],
        ['dr.melfi@hospismart.com', 'melfi123', 'Melfi', 'Jennifer', 'Psychiatrie', 'Psychothérapie', 'MED-014'],
        
        // Ophtalmologie
        ['dr.strange@hospismart.com', 'strange123', 'Strange', 'Stephen', 'Ophtalmologie', 'Neuro-Ophtalmologie', 'MED-015'],
        
        // Gynécologie
        ['dr.montgomery@hospismart.com', 'montgomery123', 'Montgomery', 'Addison', 'Gynécologie', 'Gynécologie Obstétrique', 'MED-016'],
        
        // Orthopédie
        ['dr.torres@hospismart.com', 'torres123', 'Torres', 'Callie', 'Orthopédie', 'Chirurgie Orthopédique', 'MED-017'],
    ];

    $medecinsList = [];
    foreach ($medecinsData as [$email, $pass, $nom, $prenom, $serviceName, $specialite, $matricule]) {
        $medecin = makeUser($em, $email, $pass, 'ROLE_MEDECIN', $nom, $prenom, 'MEDECIN', [
            'service' => $services[$serviceName],
            'specialite' => $specialite,
            'matricule' => $matricule
        ]);
        $medecinsList[] = $medecin;
    }

    // 3 Patients
    $patientJohn = makeUser($em, 'patient@hospismart.com',  'patient123',  'ROLE_PATIENT', 'Doe',     'John',   'PATIENT', ['genre' => 'Homme', 'telephone' => '0701020304', 'adresse' => '12 Rue de Paris']);
    $patientJane = makeUser($em, 'jane@hospismart.com',     'jane123',     'ROLE_PATIENT', 'Martin',  'Jane',   'PATIENT', ['genre' => 'Femme', 'telephone' => '0605060708', 'adresse' => '45 Av. de Tunis']);
    $patientAli  = makeUser($em, 'ali@hospismart.com',      'ali123',      'ROLE_PATIENT', 'Ben Ali', 'Mohamed','PATIENT', ['genre' => 'Homme', 'telephone' => '0612131415', 'adresse' => '8 Rue Ibn Khaldoun']);

    $em->flush(); // flush users so IDs are generated
    echo "   ✅ " . count($medecinsList) . " médecins créés\n";

    // ============================
    // 3. DISPONIBILITES (créneaux horaires médecins)
    // ============================
    echo "📅 Disponibilités...\n";
    $dispoCount = 0;
    $allDispos = []; // store for linking RDV later

    foreach ($medecinsList as $doc) {
        // Créer créneaux sur les 14 prochains jours
        for ($d = 1; $d <= 14; $d++) {
            foreach (['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'] as $hour) {
                $start = new \DateTime("+{$d} days {$hour}");
                $dayOfWeek = $start->format('N'); // 1 (Mon) to 7 (Sun)
                
                // Skip weekends
                if ($dayOfWeek >= 6) continue;
                
                // Random availability (70% chance available)
                if (rand(1, 100) > 70) continue;

                $end = (clone $start)->modify('+1 hour');
                
                $dispo = findOrCreate($em, Disponibilite::class, ['date_debut' => $start, 'medecin' => $doc], function() use ($start, $end, $doc) {
                    $d = new Disponibilite();
                    $d->setDateDebut($start)->setDateFin($end)->setEstReserve(false)->setMedecin($doc);
                    return $d;
                });
                $allDispos[] = $dispo;
                $dispoCount++;
            }
        }
    }
    $em->flush();
    echo "   ✅ {$dispoCount} créneaux créés\n";

    // ============================
    // 4. RENDEZ-VOUS (avec Disponibilité liée)
    // ============================
    echo "🗓️  Rendez-vous...\n";
    // We need to dynamically pick doctors for the random appointments
    $rdvSample = [
        ['patient' => $patientJohn, 'specialite' => 'Neurologie', 'motif' => 'Maux de tête persistants', 'statut' => 'CONFIRME'],
        ['patient' => $patientJane, 'specialite' => 'Cardiologie', 'motif' => 'Douleurs thoraciques', 'statut' => 'EN_ATTENTE'],
        ['patient' => $patientAli,  'specialite' => 'Orthopédie', 'motif' => 'Douleur genou', 'statut' => 'CONFIRME'],
        ['patient' => $patientJohn, 'specialite' => 'Dermatologie', 'motif' => 'Eczéma', 'statut' => 'EN_ATTENTE'],
    ];

    foreach ($rdvSample as $data) {
        // Find a doctor with this specialty
        $targetDocs = array_filter($medecinsList, fn($d) => $d->getServiceEntity()->getNom() === $data['specialite']);
        if (empty($targetDocs)) $targetDocs = $medecinsList; // Fallback
        
        $doc = $targetDocs[array_rand($targetDocs)];

        // Pick an available dispo for this doctor
        $docDispos = array_filter($allDispos, fn($d) => $d->getMedecin() === $doc && !$d->isEstReserve());
        $dispo = reset($docDispos);
        if (!$dispo) continue;

        $rdvDate = $dispo->getDateDebut();
        $existing = $em->getRepository(RendezVous::class)->findOneBy(['datetime' => $rdvDate, 'patient' => $data['patient']]);
        if (!$existing) {
            $rdv = new RendezVous();
            $rdv->setDatetime($rdvDate)->setStatut($data['statut'])->setMotif($data['motif']);
            $rdv->setPatient($data['patient'])->setMedecin($doc);
            $rdv->setDisponibilite($dispo);
            $dispo->setEstReserve(true);
            $em->persist($rdv);
        }
    }
    $em->flush();

    // ============================
    // 5. CONSULTATIONS & DIAGNOSTICS
    // ============================
    echo "🩺 Consultations...\n";
    // Create some consultations history
    $consData = [
        ['patient' => $patientJohn, 'medecin' => $medecinsList[0], 'motif' => 'Céphalées chroniques', 'obs' => 'IRM prescrit.', 'statut' => ConsultationStatus::TERMINEE, 'diag' => 'Migraine', 'ia' => 87.5],
        ['patient' => $patientJane, 'medecin' => $medecinsList[1], 'motif' => 'Palpitations', 'obs' => 'ECG normal.', 'statut' => ConsultationStatus::TERMINEE, 'diag' => 'Tachycardie', 'ia' => 72.0],
    ];
    foreach ($consData as $cd) {
        $existing = $em->getRepository(Consultation::class)->findOneBy(['motif' => $cd['motif'], 'patient' => $cd['patient']]);
        if (!$existing) {
            $cons = new Consultation();
            $cons->setDateHeure(new \DateTime('-' . rand(1,10) . ' days'));
            $cons->setMotif($cd['motif'])->setObservations($cd['obs'])->setStatut($cd['statut']);
            $cons->setPatient($cd['patient'])->setMedecin($cd['medecin']);
            $em->persist($cons);

            $diag = new Diagnostic();
            $diag->setContenu($cd['diag'])->setProbabiliteIa($cd['ia']);
            $em->persist($diag);
        }
    }

    // ============================
    // 6. CATEGORIES & MEDICAMENTS & STOCK
    // ============================
    echo "💊 Médicaments...\n";
    $catsData = ['Antalgiques' => 'Soulagement de la douleur', 'Antibiotiques' => 'Lutte contre les infections', 'Anti-inflammatoires' => 'Réduction des inflammations', 'Vitamines' => 'Suppléments vitaminiques', 'Cardiologie' => 'Traitement cardiaque'];
    $cats = [];
    foreach ($catsData as $nom => $desc) {
        $cats[$nom] = findOrCreate($em, Categorie::class, ['nom' => $nom], function() use ($nom, $desc) {
            $c = new Categorie(); $c->setNom($nom); $c->setDescription($desc); return $c;
        });
    }

    $medsData = [
        ['Doliprane 1000mg',   200, 5.50,  'Antalgiques',        '+18 months'],
        ['Amoxicilline 500mg', 80,  12.90, 'Antibiotiques',      '+12 months'],
        ['Ibuprofène 400mg',   150, 7.20,  'Anti-inflammatoires', '+24 months'],
        ['Vitamine C 1000mg',  300, 3.90,  'Vitamines',          '+36 months'],
        ['Aspirine Cardio',    120, 8.50,  'Cardiologie',        '+18 months'],
        ['Paracétamol 500mg',  250, 4.10,  'Antalgiques',        '+20 months'],
    ];
    foreach ($medsData as [$nom, $stock, $prix, $catName, $peremption]) {
        $existing = $em->getRepository(Medicament::class)->findOneBy(['nom' => $nom]);
        if (!$existing) {
            $m = new Medicament();
            $m->setNom($nom); $m->setQuantite($stock); $m->setPrixUnitaire($prix); $m->setSeuilAlerte(15);
            $m->setDatePeremption(new \DateTime($peremption));
            $m->setCategorie($cats[$catName] ?? null);
            $em->persist($m);
            // Mouvement Stock Entrée
            $mv = new MouvementStock();
            $mv->setMedicament($m)->setType('ENTREE')->setQuantite($stock);
            $mv->setDateMouvement(new \DateTime('-' . rand(5,30) . ' days'));
            $mv->setCommentaire("Approvisionnement initial $nom");
            $em->persist($mv);
        }
    }

    // ============================
    // 7. EVENEMENTS
    // ============================
    echo "🎉 Événements...\n";
    $eventsData = [
        ['Conférence : IA en Médecine',  'reunion', '+1 week',  'Amphithéâtre A'],
        ['Journée Don du Sang',          'autre',   '+2 weeks', 'Hall Principal'],
        ['Formation Sécurité Patient',   'reunion', '+3 weeks', 'Salle de Formation B'],
        ['Maintenance Bloc Opératoire',  'autre',   '+10 days', 'Bloc 3'],
    ];
    foreach ($eventsData as [$titre, $type, $startStr, $lieu]) {
        findOrCreate($em, Evenement::class, ['titre' => $titre], function() use ($titre, $type, $startStr, $lieu, $admin) {
            $e = new Evenement();
            $e->setTitre($titre)->setDescription("Événement: $titre")->setTypeEvenement($type);
            $e->setDateDebut(new \DateTime($startStr));
            $e->setDateFin((new \DateTime($startStr))->modify('+3 hours'));
            $e->setLieu($lieu)->setStatut('planifié')->setCreateur($admin);
            return $e;
        });
    }

    // ============================
    // 8. RECLAMATIONS & REPONSES
    // ============================
    echo "📝 Réclamations...\n";
    $recData = [
        ['Temps d\'attente excessif',    'L\'attente aux urgences dépasse 3 heures.',       'Service',  'Haute',   'Traité',     'John Doe',     'patient@hospismart.com'],
        ['Propreté des sanitaires',      'Les toilettes du 2ème étage sont mal entretenues.','Hygiène',  'Moyenne', 'En cours',   'Jane Martin',  'jane@hospismart.com'],
        ['Personnel désagréable',        'Accueil peu professionnel à la réception.',        'Personnel','Haute',   'En attente', 'Mohamed Ben Ali','ali@hospismart.com'],
    ];
    foreach ($recData as [$titre, $desc, $categorie, $priorite, $statut, $nomP, $emailP]) {
        $rec = findOrCreate($em, Reclamation::class, ['titre' => $titre], function() use ($titre, $desc, $categorie, $priorite, $statut, $nomP, $emailP) {
            $r = new Reclamation();
            $r->setTitre($titre)->setDescription($desc)->setCategorie($categorie);
            $r->setPriorite($priorite)->setStatut($statut);
            $r->setNomPatient($nomP)->setEmail($emailP);
            return $r;
        });
        // Ajouter une réponse pour les réclamations traitées
        if ($statut === 'Traité') {
            $existingRep = $em->getRepository(Reponse::class)->findOneBy(['reclamation' => $rec]);
            if (!$existingRep) {
                $rep = new Reponse();
                $rep->setContenu("Merci pour votre retour. Nous avons pris des mesures correctives pour résoudre ce problème.");
                $rep->setAdminNom('Admin System')->setAdminEmail('admin@hospismart.com');
                $rep->setReclamation($rec);
                $em->persist($rep);
            }
        }
    }

    // ============================
    // 9. EQUIPEMENTS
    // ============================
    echo "🏥 Équipements...\n";
    $equipData = [
        ['IRM Siemens Magnetom',        'IRM-001', 'Bon',       'Propriété',  'Radiologie'],
        ['Scanner Philips Ingenuity',    'SC-002',  'Bon',       'Propriété',  'Radiologie'],
        ['Échographe GE Vivid',          'ECH-003', 'Moyen',     'Location',   'Cardiologie'],
        ['Défibrillateur Zoll',          'DEF-004', 'Bon',       'Propriété',  'Urgences'],
        ['Incubateur Dräger',            'INC-005', 'Bon',       'Propriété',  'Pédiatrie'],
        ['Dermatoscope Heine Delta',     'DRM-006', 'Bon',       'Propriété',  'Dermatologie'],
    ];
    foreach ($equipData as [$nom, $ref, $etat, $relation, $svc]) {
        findOrCreate($em, Equipement::class, ['reference' => $ref], function() use ($nom, $ref, $etat, $relation, $svc, $services) {
            $e = new Equipement();
            $e->setNom($nom)->setReference($ref)->setEtat($etat)->setRelation($relation);
            $e->setService($services[$svc] ?? reset($services));
            return $e;
        });
    }

    // ============================
    // 10. CAMPAGNES
    // ============================
    echo "📣 Campagnes...\n";
    $campData = [
        ['Stop Tabac',               'Prévention',    'Campagne de lutte contre le tabagisme dans l\'hôpital.', 5000.0],
        ['Vaccination Grippe 2026',  'Vaccination',   'Campagne de vaccination antigrippale pour le personnel et les patients.', 12000.0],
    ];
    foreach ($campData as [$titre, $theme, $desc, $budget]) {
        findOrCreate($em, Campagne::class, ['titre' => $titre], function() use ($titre, $theme, $desc, $budget) {
            $c = new Campagne();
            $c->setTitre($titre)->setTheme($theme)->setDescription($desc);
            $c->setDateDebut(new \DateTime('+1 month'));
            $c->setDateFin(new \DateTime('+2 months'));
            $c->setBudget($budget);
            return $c;
        });
    }

    // ============================
    // FLUSH FINAL
    // ============================
    $em->flush();

    echo "\n";
    echo "╔═══════════════════════════════════════════╗\n";
    echo "║    ✅ BASE DE DONNÉES 100% REMPLIE !      ║\n";
    echo "╠═══════════════════════════════════════════╣\n";
    echo "║  👤 Util. : 1 Admin / 17 Méd / 3 Patients ║\n";
    echo "║  🏥 11 Services Créés                     ║\n";
    echo "║  📅 Créneaux Générés                      ║\n";
    echo "║  💊 Médicaments & Stock OK                ║\n";
    echo "║  🎉 Événements & Campagnes OK             ║\n";
    echo "╠═══════════════════════════════════════════╣\n";
    echo "║  🔑 admin@hospismart.com   / admin123     ║\n";
    echo "║  🔑 dr.house@hospismart.com / house123    ║\n";
    echo "╚═══════════════════════════════════════════╝\n";

} catch (\Throwable $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
