<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Entity\Notification;
use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\Service;
use App\Enum\RendezVousStatut;

use App\Repository\RendezVousRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Consultation;
use Symfony\Component\Mailer\MailerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mime\Email;


#[Route('/medecin')]
final class MedecinController extends AbstractController
{
    /**
     * MÉTHODE PRIVÉE : Centralise la récupération du médecin
     */
    private function getConnectedMedecin(): ?User
    {
        /** @var User $user */
        $user = $this->getUser();
        // On vérifie que l'utilisateur est bien un médecin
        return ($user && $user->getType() === 'MEDECIN') ? $user : null;
    }

    /**
     * DASHBOARD
     */
    #[Route('/dashboard', name: 'app_medecin_dashboard', methods: ['GET'])]
    public function dashboard(RendezVousRepository $rdvRepo, UserRepository $userRepo, \App\Repository\NotificationRepository $notifRepo): Response
    {
        $medecin = $this->getConnectedMedecin();
        if (!$medecin) return $this->redirectToRoute('app_login');

        // 1. Prochain RDV
        $prochainRdv = $rdvRepo->findOneBy(
            ['medecin' => $medecin, 'statut' => 'CONFIRME'],
            ['datetime' => 'ASC']
        );

        // 2. Notifications (Demandes en attente) pour la liste
        $notifsEnAttente = $rdvRepo->findBy(
            ['medecin' => $medecin, 'statut' => 'EN_ATTENTE'],
            ['datetime' => 'DESC']
        );

        // Notifications non lues
        $unreadNotifs = $notifRepo->count(['user' => $medecin, 'isRead' => false]);

        // 3. Statistiques du jour
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

        $tauxOccupation = min(100, ($rdvConfirmesAujourdhui / 15) * 100);

        return $this->render('medecin/dashboard.html.twig', [
            'medecin' => $medecin,
            'demandes' => $rdvRepo->findBy(['medecin' => $medecin], ['datetime' => 'ASC']),
            'prochain_rdv' => $prochainRdv,
            'rdv_du_jour' => $rdvConfirmesAujourdhui,
            'unread_notifications_count' => $unreadNotifs,
            'notifications_list' => $notifsEnAttente,
            'total_patients' => count($userRepo->findBy(['type' => 'PATIENT'])),
            'taux_occupation' => round($tauxOccupation)
        ]);
    }
    

    /**
     * MES RENDEZ-VOUS
     */
    #[Route('/demandes-rendezvous', name: 'app_medecin_demandes_rdv', methods: ['GET'])]
    public function demandesRendezVous(RendezVousRepository $rdvRepo): Response
    {
        $medecin = $this->getConnectedMedecin();
        if (!$medecin) return $this->redirectToRoute('app_login');

        $demandes = $rdvRepo->findBy(['medecin' => $medecin], ['datetime' => 'DESC']);

        return $this->render('medecin/mesRendezvous.html.twig', [
            'demandes' => $demandes,
            'medecin' => $medecin,
            'totalRDV' => count($demandes)
        ]);
    }

    /**
     * MES PATIENTS
     */
    #[Route('/mes-patients', name: 'app_medecin_patients', methods: ['GET'])]
    public function listPatients(UserRepository $userRepo): Response
    {
        $medecin = $this->getConnectedMedecin();
        if (!$medecin) return $this->redirectToRoute('app_login');
        
        $patients = $userRepo->findBy(['type' => 'PATIENT']);

        return $this->render('medecin/patients.html.twig', [
            'medecin' => $medecin,
            'patients' => $patients,
        ]);
    }

    /**
     * MON PROFIL
     */
    #[Route('/mon-profil', name: 'app_medecin_profil', methods: ['GET'])]
    public function profil(): Response
    {
        $medecin = $this->getConnectedMedecin();
        if (!$medecin) return $this->redirectToRoute('app_login');

        return $this->render('medecin/profil.html.twig', [
            'medecin' => $medecin
        ]);
    }

    /**
     * GESTION DES DISPONIBILITÉS
     */
    #[Route('/mes-dispos/gestion', name: 'app_medecin_dispo_index')]
    public function mesDispos(EntityManagerInterface $em, Request $request, RendezVousRepository $rdvRepo): Response
    {
        $medecin = $this->getConnectedMedecin();
        if (!$medecin) return $this->redirectToRoute('app_login');

        $dispo = new Disponibilite();
        $dispo->setMedecin($medecin);
        $dispo->setEstReserve(false);

        $form = $this->createForm(\App\Form\DisponibiliteType::class, $dispo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dispo->getDateDebut() < new \DateTime()) {
                $this->addFlash('danger', 'Date passée impossible.');
            } else {
                $em->persist($dispo);
                $em->flush();
                $this->addFlash('success', 'Créneau ajouté.');
            }
            return $this->redirectToRoute('app_medecin_dispo_index');
        }

        $dispos = $em->getRepository(Disponibilite::class)->findBy(['medecin' => $medecin], ['date_debut' => 'ASC']);

        return $this->render('medecin/dispo.html.twig', [
            'form' => $form->createView(),
            'dispos' => $dispos,
            'medecin' => $medecin,
            'nbDemandesEnAttente' => count($rdvRepo->findBy(['medecin' => $medecin, 'statut' => 'EN_ATTENTE'])),
        ]);
    }

    #[Route('/profil/{id}', name: 'app_medecin_show', methods: ['GET'])]
    public function show(User $medecin): Response
    {
        if ($medecin->getType() !== 'MEDECIN') throw $this->createNotFoundException();
        return $this->render('medecin/show.html.twig', ['medecin' => $medecin]);
    }

    /**
     * FILTRE PAR SERVICE
     */
    #[Route('/recherche-patient/service/{id}', name: 'app_medecins_par_service', methods: ['GET'])]
    public function parService(Service $service, UserRepository $userRepository): Response
    {
        // On récupère les médecins liés à ce service
        // Mapping: user.service_entity
        $medecins = $userRepository->findBy(['service_entity' => $service, 'type' => 'MEDECIN']);

        return $this->render('medecin/liste_par_service.html.twig', [
            'medecins' => $medecins,
            'service' => $service, 
        ]);
    }

    #[Route('/medecin/patient/{id}', name: 'app_medecin_patient_show')]
    public function showPatient(User $patient): Response
    {
        // Décommentez la ligne suivante pour voir ce que contient réellement le type de cet utilisateur
        // dd($patient->getType()); 

        if (strtoupper($patient->getType()) !== 'PATIENT') {
            throw $this->createNotFoundException("L'utilisateur ID " . $patient->getId() . " n'est pas un PATIENT (Type actuel: " . $patient->getType() . ")");
        }

        return $this->render('medecin/patient_show.html.twig', [
            'patient' => $patient
        ]);
    }

    /**
     * ACTIONS RDV (ACCEPTER)
     */
    #[Route('/rendezvous/{id}/accepter', name: 'app_medecin_rdv_accepter', methods: ['POST'])]
    public function accepterRendezVous(RendezVous $rendezVous, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('accepter' . $rendezVous->getId(), $request->request->get('_token'))) {
            $rendezVous->setStatut(RendezVousStatut::CONFIRME->value);

            $notification = new Notification();
            $notification->setUser($rendezVous->getPatient()); // User de type PATIENT
            $notification->setContent("✅ RDV confirmé pour le " . $rendezVous->getDatetime()->format('d/m à H:i'));
            $notification->setCreatedAt(new \DateTimeImmutable());
            $notification->setIsRead(false);
            
            $em->persist($notification);
            $em->flush();
            $this->addFlash('success', 'Rendez-vous confirmé.');
        }
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_medecin_dashboard')));
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
            $notification->setUser($rendezVous->getPatient());
            $notification->setContent("❌ Votre demande de RDV du " . $rendezVous->getDatetime()->format('d/m') . " a été refusée.");
            $notification->setCreatedAt(new \DateTimeImmutable());
            $notification->setIsRead(false);
            
            $em->persist($notification);
            $em->flush();
            $this->addFlash('info', 'Rendez-vous refusé.');
        }
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_medecin_demandes_rdv'));
    }

    #[Route('/consultation/{id}/envoyer', name: 'app_medecin_envoyer_ordonnance')]
    public function envoyerOrdonnance(Consultation $consultation, MailerInterface $mailer): Response
    {
        // 1. Préparation du PDF
        $pdfOptions = new Options();
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('medecin/consultation/ordonnance_pdf.html.twig', [
            'consultation' => $consultation,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 2. Création de l'Email
        $email = (new Email())
            ->from('manarferjanii@gmail.com')
            ->to($consultation->getPatient()->getEmail())
            ->addBcc('manarferjanii@gmail.com')
            ->subject('Votre Ordonnance - Hospismart')
            ->html('<p>Bonjour, vous trouverez ci-joint votre ordonnance.</p>')
            ->attach($dompdf->output(), "ordonnance.pdf", 'application/pdf');

        // 3. Envoi avec capture d'erreur
        try {
            $mailer->send($email);
            $this->addFlash('success', 'Email envoyé au patient avec succès !');
        } catch (TransportExceptionInterface $e) {
            // Cela affichera l'erreur technique précise (ex: problème de mot de passe Gmail)
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_medecin_dashboard');
    }
    #[Route('/', name: 'app_medecin_index', methods: ['GET'])]
    public function index(UserRepository $UserRepository): Response
    {
        return $this->render('medecin/index.html.twig', ['medecins' => $UserRepository->findAll()]);
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
            $notification->setUser($rdv->getPatient());
            $notification->setContent("⚠️ Le Dr. " . $rdv->getMedecin()->getNom() . " a annulé le RDV du " . $rdv->getDatetime()->format('d/m') . ".");
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
        int $id, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
      
        $dispo = $em->getRepository(Disponibilite::class)->find($id);

        if (!$dispo) {
            $this->addFlash('danger', 'Ce créneau n\'existe plus ou a déjà été supprimé.');
            return $this->redirectToRoute('app_medecin_dispo_index');
        }

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

        $dateDebut = new \DateTime($request->request->get('date_debut'));
        $dateFin = new \DateTime($request->request->get('date_fin'));

        $dispo->setDateDebut($dateDebut);
        $dispo->setDateFin($dateFin);
        
        $em->flush();
        $this->addFlash('success', 'Créneau mis à jour avec succès !');

        return $this->redirectToRoute('app_medecin_dispo_index');
    }


#[Route('/medecin/rdv/modifier-date/{id}', name: 'app_medecin_rdv_modifier_date', methods: ['POST'])]
public function modifierDate(RendezVous $rdv, Request $request, EntityManagerInterface $em): Response
{
    $nouvelleDateStr = $request->request->get('nouvelle_date');
    
    if ($nouvelleDateStr) {
        $nouvelleDate = new \DateTime($nouvelleDateStr);
        $ancienneDate = $rdv->getDatetime()->format('d/m/Y à H:i');
        
        // 1. Mise à jour du RDV
        $rdv->setDatetime($nouvelleDate);
        $rdv->setStatut('CONFIRME');
        
        // Synchronisation de la disponibilité
        if ($rdv->getDisponibilite()) {
            $rdv->getDisponibilite()->setDateDebut($nouvelleDate);
            $dateFin = clone $nouvelleDate;
            $dateFin->modify('+30 minutes'); 
            $rdv->getDisponibilite()->setDateFin($dateFin);
            $rdv->getDisponibilite()->setEstReserve(true);
        }

        // 2. CRÉATION DE LA NOTIFICATION (Adaptée à ton entité)
        $notification = new Notification();
        
        // On utilise setUser() car c'est le nom dans ton entité
        $notification->setUser($rdv->getPatient()); 
        
        // On utilise setContent() au lieu de setMessage()
        $notification->setContent(sprintf(
            "Le Dr. %s a modifié la date de votre rendez-vous. Nouvelle date : %s (au lieu du %s).",
            $rdv->getMedecin()->getNom(),
            $nouvelleDate->format('d/m/Y à H:i'),
            $ancienneDate
        ));
        
        // createdAt et isRead sont déjà gérés par ton __construct, 
        // mais tu peux les forcer si nécessaire :
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $em->persist($notification);
        $em->flush();

        $this->addFlash('success', 'Date modifiée et notification enregistrée pour le patient.');
    }

    return $this->redirect($request->headers->get('referer', $this->generateUrl('app_medecin_dashboard')));}


#[Route('/mon-profil/update', name: 'app_medecin_profil_update', methods: ['POST'])]
    public function updateProfil(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupération et mise à jour des données
        $user->setNom($request->request->get('nom'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));
        
        // Optionnel : si vous avez ajouté le champ téléphone dans le Twig
        if ($request->request->has('telephone')) {
            $user->setTelephone($request->request->get('telephone'));
        }

        $entityManager->flush();

        $this->addFlash('success', 'Votre profil professionnel a été mis à jour.');

        return $this->redirectToRoute('app_medecin_profil');
    }
}