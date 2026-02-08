<?php

namespace App\Controller;

use App\Entity\Medecin;
use App\Entity\Notification;
use App\Entity\Disponibilite;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Service;
use App\Enum\RendezVousStatut;
use App\Form\MedecinType;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use App\Repository\PatientRepository; // Import ajouté
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/medecin')]
final class MedecinController extends AbstractController
{
    /**
     * MÉTHODE PRIVÉE : Centralise la récupération du médecin
     */
    private function getConnectedMedecin(MedecinRepository $medecinRepo): ?Medecin
    {
        return $this->getUser()?->getMedecin() ?: $medecinRepo->findAll()[0] ?? null;
    }

    /**
     * DASHBOARD
     */
    #[Route('/dashboard', name: 'app_medecin_dashboard', methods: ['GET'])]
    public function dashboard(RendezVousRepository $rdvRepo, MedecinRepository $medecinRepo, PatientRepository $patientRepo): Response
    {
        $medecin = $this->getConnectedMedecin($medecinRepo);
        
        // 1. Prochain RDV
        $prochainRdv = $rdvRepo->findOneBy(
            ['medecin' => $medecin, 'statut' => 'CONFIRME'],
            ['datetime' => 'ASC']
        );

        // 2. Notifications (En attente)
        $notifsEnAttente = $rdvRepo->findBy(
            ['medecin' => $medecin, 'statut' => 'EN_ATTENTE'],
            ['datetime' => 'DESC']
        );

        // 3. Calcul du Taux d'Occupation (RDV de TOUTE la journée d'aujourd'hui)
        $capaciteMaxQuotidienne = 15;
        $aujourdhuiDebut = new \DateTime('today 00:00:00');
        $aujourdhuiFin = new \DateTime('today 23:59:59');

        $rdvConfirmesAujourdhui = $rdvRepo->createQueryBuilder('r')
            ->select('count(r.id)')
            ->where('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.datetime BETWEEN :debut AND :fin')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', 'CONFIRME')
            ->setParameter('debut', $aujourdhuiDebut)
            ->setParameter('fin', $aujourdhuiFin)
            ->getQuery()
            ->getSingleScalarResult();

        // Calcul sécurisé du taux
        $tauxOccupation = ($rdvConfirmesAujourdhui / $capaciteMaxQuotidienne) * 100;
        if ($tauxOccupation > 100) $tauxOccupation = 100;

        // 4. Définition de la variable manquante
        $totalPatients = count($patientRepo->findAll());

        return $this->render('medecin/dashboard.html.twig', [
            'medecin' => $medecin,
            'demandes' => $rdvRepo->findBy(['medecin' => $medecin], ['datetime' => 'ASC']),
            'prochain_rdv' => $prochainRdv,
            'rdv_du_jour' => $rdvConfirmesAujourdhui,
            'demandes_attente' => count($notifsEnAttente),
            'notifications_list' => $notifsEnAttente,
            'total_patients' => $totalPatients, // <--- La variable est bien définie maintenant
            'taux_occupation' => round($tauxOccupation)
        ]);
    }

    /**
     * MES RENDEZ-VOUS
     */
    #[Route('/demandes-rendezvous', name: 'app_medecin_demandes_rdv', methods: ['GET'])]
    public function demandesRendezVous(RendezVousRepository $rdvRepo, MedecinRepository $medecinRepo): Response
    {
        $medecin = $this->getConnectedMedecin($medecinRepo);
        if (!$medecin) { return $this->redirectToRoute('app_medecin_index'); }

        $demandes = $rdvRepo->findBy(['medecin' => $medecin], ['datetime' => 'DESC']);

        return $this->render('medecin/mesRendezvous.html.twig', [
            'demandes' => $demandes,
            'medecin' => $medecin,
            'totalRDV' => count($demandes)
        ]);
    }

    /**
     * MES PATIENTS (Correction Route et Données)
     */
    #[Route('/mes-patients', name: 'app_medecin_patients', methods: ['GET'])]
    public function listPatients(PatientRepository $patientRepo, MedecinRepository $medecinRepo): Response
    {
        $medecin = $this->getConnectedMedecin($medecinRepo);
        $patients = $patientRepo->findAll(); 

        return $this->render('medecin/patients.html.twig', [
            'medecin' => $medecin,
            'patients' => $patients,
        ]);
    }

    /**
     * MON PROFIL (Correction Affichage Nom)
     */
    #[Route('/mon-profil', name: 'app_medecin_profil', methods: ['GET'])]
    public function profil(MedecinRepository $medecinRepo): Response
    {
        $medecin = $this->getConnectedMedecin($medecinRepo);

        return $this->render('medecin/profil.html.twig', [
            'medecin' => $medecin
        ]);
    }

    /**
     * GESTION DES DISPONIBILITÉS
     */
    #[Route('/mes-dispos/gestion', name: 'app_medecin_dispo_index')]
    public function mesDispos(EntityManagerInterface $em, Request $request, MedecinRepository $medecinRepo, RendezVousRepository $rdvRepo): Response
    {
        $medecin = $this->getConnectedMedecin($medecinRepo);
        if (!$medecin) { return new Response("Erreur : Aucun médecin en base."); }

        $dispo = new \App\Entity\Disponibilite();
        $dispo->setMedecin($medecin);
        $dispo->setEstReserve(false);

        $form = $this->createForm(\App\Form\DisponibiliteType::class, $dispo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $maintenant = new \DateTime();
            if ($dispo->getDateDebut() < $maintenant) {
                $this->addFlash('danger', 'Impossible de créer un créneau dans le passé !');
                return $this->redirectToRoute('app_medecin_dispo_index');
            }
            $em->persist($dispo);
            $em->flush();
            $this->addFlash('success', 'Nouveau créneau ajouté !');
            return $this->redirectToRoute('app_medecin_dispo_index');
        }

        return $this->render('medecin/dispo.html.twig', [
            'form' => $form->createView(),
            // Remplacer l'ancien findBy par ceci :
            'dispos' => $em->getRepository(\App\Entity\Disponibilite::class)
                ->createQueryBuilder('d')
                ->where('d.medecin = :medecin')
                ->andWhere('d.date_fin >= :maintenant') // On garde ce qui finit aujourd'hui ou plus tard
                ->setParameter('medecin', $medecin)
                ->setParameter('maintenant', new \DateTime())
                ->orderBy('d.date_debut', 'ASC')
                ->getQuery()
                ->getResult(),
            'medecin' => $medecin,
            'nbDemandesEnAttente' => count($rdvRepo->findBy(['medecin' => $medecin, 'statut' => 'EN_ATTENTE'])),
        ]);
    }

    /**
     * RECHERCHE MÉDECIN (Côté Patient)
     */
#[Route('/recherche-patient', name: 'app_medecin_recherche', methods: ['GET'])]
public function recherche(MedecinRepository $medecinRepository, ServiceRepository $serviceRepository, Request $request): Response
{
    $nomRecherche = $request->query->get('nom');

    if ($nomRecherche) {
        // On cherche les médecins qui correspondent au nom
        $medecins = $medecinRepository->findByNomLike($nomRecherche);

        // SI ON TROUVE EXACTEMENT UN MÉDECIN : Redirection directe
        if (count($medecins) === 1) {
            return $this->redirectToRoute('app_medecin_show', [
                'id' => $medecins[0]->getId()
            ]);
        }
        
        // Si plusieurs médecins portent le même nom, on les affiche dans la liste
    } else {
        $medecins = $medecinRepository->findAll();
    }

    // Si aucun résultat ou plusieurs résultats, on reste sur la page de recherche classique
    return $this->render('patient/recherche_medecin.html.twig', [
        'medecins' => $medecins,
        'services' => $serviceRepository->findAll(),
    ]);
}

    #[Route('/medecin/profil/{id}', name: 'app_medecin_show', methods: ['GET'])]
public function show(Medecin $medecin): Response
{
    // Symfony cherche automatiquement le Medecin ayant cet ID
    // S'il ne le trouve pas, il renverra une erreur 404
    
    return $this->render('medecin/show.html.twig', [
        'medecin' => $medecin,
    ]);
}
/**
     * FILTRE PAR SERVICE
     */
    #[Route('/recherche-patient/service/{id}', name: 'app_medecins_par_service', methods: ['GET'])]
    public function parService(Service $service, MedecinRepository $medecinRepository, ServiceRepository $serviceRepository): Response
    {
        // On récupère les médecins liés à ce service
        $medecins = $medecinRepository->findBy(['service' => $service]);

        // ATTENTION : Changez le nom du template ci-dessous par le nom réel de votre fichier
        return $this->render('medecin/liste_par_service.html.twig', [
            'medecins' => $medecins,
            'service' => $service, // On passe l'objet service pour le titre {{ service.nom }}
        ]);
    }
    #[Route('/medecin/patient/{id}', name: 'app_medecin_patient_show')]
public function showPatient(Patient $patient): Response
{
    return $this->render('medecin/patient_show.html.twig', [
        'patient' => $patient,
    ]);
}

/**
 * ACTIONS RDV (ACCEPTR)
 */
#[Route('/rendezvous/{id}/accepter', name: 'app_medecin_rdv_accepter', methods: ['POST'])]
public function accepterRendezVous(RendezVous $rendezVous, Request $request, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('accepter' . $rendezVous->getId(), $request->request->get('_token'))) {
        $rendezVous->setStatut(RendezVousStatut::CONFIRME->value);

        $notification = new Notification();
        $notification->setPatient($rendezVous->getPatient());
        $notification->setMessage("✅ Votre RDV du " . $rendezVous->getDatetime()->format('d/m à H:i') . " est confirmé par le Dr. " . $rendezVous->getMedecin()->getNom());
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);
        
        $em->persist($notification);
        $em->flush();
        $this->addFlash('success', 'Rendez-vous confirmé.');
    }
    return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_medecin_demandes_rdv'));
}

/**
 * ACTIONS RDV (REFUSER)
 */
#[Route('/rendezvous/{id}/refuser', name: 'app_medecin_rdv_refuser', methods: ['POST'])]
public function refuserRendezVous(RendezVous $rendezVous, Request $request, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('refuser' . $rendezVous->getId(), $request->request->get('_token'))) {
        $rendezVous->setStatut(RendezVousStatut::REFUSE->value);
        if ($dispo = $rendezVous->getDisponibilite()) { 
            $dispo->setEstReserve(false); 
        }

        $notification = new Notification();
        $notification->setPatient($rendezVous->getPatient());
        $notification->setMessage("❌ Votre demande de RDV du " . $rendezVous->getDatetime()->format('d/m') . " a été refusée.");
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);
        
        $em->persist($notification);
        $em->flush();
        $this->addFlash('info', 'Rendez-vous refusé.');
    }
    return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_medecin_demandes_rdv'));
}

/**
 * ACTIONS RDV (ANNULER)
 */
#[Route('/medecin/rdv/{id}/annuler', name: 'app_medecin_rdv_annuler', methods: ['POST'])]
public function annulerRdv(Request $request, RendezVous $rdv, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('annuler' . $rdv->getId(), $request->request->get('_token'))) {
        $rdv->setStatut('ANNULE'); 

        $notification = new Notification();
        $notification->setPatient($rdv->getPatient());
        $notification->setMessage("⚠️ Le Dr. " . $rdv->getMedecin()->getNom() . " a annulé le RDV du " . $rdv->getDatetime()->format('d/m') . ".");
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);
        
        $em->persist($notification);
        $em->flush();
        $this->addFlash('info', 'Le rendez-vous a été annulé.');
    }
    return $this->redirectToRoute('app_medecin_demandes_rdv');
}

#[Route('/medecin/disponibilite/{id}/delete', name: 'app_medecin_dispo_delete', methods: ['POST'])]
public function deleteDisponibilite(
    int $id, // On demande l'ID au lieu de l'objet directement
    Request $request, 
    EntityManagerInterface $em
): Response {
    // On cherche l'objet nous-mêmes
    $dispo = $em->getRepository(Disponibilite::class)->find($id);

    // Si l'objet n'existe pas, on redirige avec un message propre au lieu de crasher
    if (!$dispo) {
        $this->addFlash('danger', 'Ce créneau n\'existe plus ou a déjà été supprimé.');
        return $this->redirectToRoute('app_medecin_dispo_index');
    }

    // Vérification de sécurité avec le jeton CSRF
    if ($this->isCsrfTokenValid('delete' . $dispo->getId(), $request->request->get('_token'))) {
        
        if ($dispo->isEstReserve()) {
            $this->addFlash('danger', 'Impossible de supprimer un créneau déjà réservé.');
        } else {
            $em->remove($dispo);
            $em->flush();
            $this->addFlash('success', 'Le créneau a été supprimé avec succès.');
        }
    }

    return $this->redirectToRoute('app_medecin_dispo_index');
}

#[Route('/medecin/disponibilite/{id}/edit', name: 'app_medecin_dispo_edit', methods: ['POST'])]
public function editDispo(int $id, Request $request, EntityManagerInterface $em): Response
{
    $dispo = $em->getRepository(Disponibilite::class)->find($id);

    if (!$dispo || $dispo->isEstReserve()) {
        $this->addFlash('danger', 'Modification impossible.');
        return $this->redirectToRoute('app_medecin_dispo_index');
    }

    // Récupération des données du formulaire envoyé en POST
    $dateDebut = new \DateTime($request->request->get('date_debut'));
    $dateFin = new \DateTime($request->request->get('date_fin'));

    $dispo->setDateDebut($dateDebut);
    $dispo->setDateFin($dateFin);
    
    $em->flush();
    $this->addFlash('success', 'Créneau mis à jour avec succès !');

    return $this->redirectToRoute('app_medecin_dispo_index');
}

    /**
     * CRUD MÉDECINS (INDEX, NEW, SHOW, EDIT, DELETE)
     */
    #[Route('/', name: 'app_medecin_index', methods: ['GET'])]
    public function index(MedecinRepository $medecinRepository): Response
    {
        return $this->render('medecin/index.html.twig', ['medecins' => $medecinRepository->findAll()]);
    }

    #[Route('/new', name: 'app_medecin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $medecin = new Medecin();
        $form = $this->createForm(MedecinType::class, $medecin);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($medecin);
            $entityManager->flush();
            return $this->redirectToRoute('app_medecin_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('medecin/new.html.twig', ['medecin' => $medecin, 'form' => $form]);
    }



    #[Route('/{id}/edit', name: 'app_medecin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Medecin $medecin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MedecinType::class, $medecin);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_medecin_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('medecin/edit.html.twig', ['medecin' => $medecin, 'form' => $form]);
    }

    #[Route('/{id}/delete', name: 'app_medecin_delete', methods: ['POST'])]
    public function delete(Request $request, Medecin $medecin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$medecin->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($medecin);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_medecin_index', [], Response::HTTP_SEE_OTHER);
    }
}