<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\Disponibilite;
use App\Entity\User; // On utilise User à la place de Patient
use App\Entity\Notification;
use App\Form\RendezVousType;
use App\Repository\UserRepository; // Import du UserRepository
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\AIService;

class RendezVousController extends AbstractController
{
#[Route('/rendezvous/dispo/{id}', name: 'app_rendezvous_dispo')]
public function afficherDispos(int $id, UserRepository $medecinRepo, DisponibiliteRepository $dispoRepo): Response 
{
    $medecin = $medecinRepo->find($id);


    $dispos = $dispoRepo->findBy(
        ['medecin' => $medecin],
        ['date_debut' => 'ASC']
    );

    return $this->render('rendezvous/calendrier.html.twig', [
        'medecin' => $medecin,
        'disponibilites' => $dispos
    ]);
}

#[Route('/rendezvous/reserver/{id}', name: 'app_rendezvous_reserver')]
public function reserver(Disponibilite $dispo, Request $request, EntityManagerInterface $em, AIService $aiService): Response 
{
    // 1. SÉCURITÉ : Si un patient accède à l'URL d'un créneau déjà réservé entre-temps
    if ($dispo->isEstReserve()) {
        $this->addFlash('danger', 'Désolé, ce créneau n\'est plus disponible.');
        return $this->redirectToRoute('app_medecin_recherche');
    }
    
    $rdv = new RendezVous();
    $rdv->setMedecin($dispo->getMedecin());
    $dateDebut = $dispo->getDateDebut();
    if ($dateDebut !== null) {
        $rdv->setDatetime($dateDebut);
    }
    $rdv->setDisponibilite($dispo);
    $rdv->setStatut('EN_ATTENTE');
    
    /** @var \App\Entity\User|null $user */
    $user = $this->getUser();
    if ($user) { 
        $rdv->setPatient($user); 
    }

    $form = $this->createForm(RendezVousType::class, $rdv);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $motif = $rdv->getMotif();
        $rdv->setPriorite($motif ? $aiService->calculerPriorite($motif) : 2);

        // 2. IMPORTANT : On marque la disponibilité comme réservée
        // Cela va la faire disparaître du calendrier grâce au filtre Twig
        $dispo->setEstReserve(true);
        
        $em->persist($rdv);
        
        // 3. Créer une notification pour le médecin
        $medecin = $rdv->getMedecin();
        $patient = $rdv->getPatient();
        $notification = new Notification();
        $notification->setUser($medecin);
        $notification->setContent(
            "📅 Nouvelle demande de RDV de " . 
            $patient->getPrenom() . " " . $patient->getNom() . 
            " pour le " . $rdv->getDatetime()->format('d/m/Y à H:i')
        );
        $em->persist($notification);
        
        $em->flush();

        $this->addFlash('success', 'Votre demande a été envoyée. Ce créneau vous est maintenant réservé en attente de validation.');
        return $this->redirectToRoute('app_medecin_recherche'); 
    }

    return $this->render('rendezvous/finaliser.html.twig', [
        'form' => $form->createView(),
        'dispo' => $dispo
    ]);
}

#[Route('/medecin/rendezvous/{id}/accepter', name: 'app_medecin_rdv_accepter')]
public function accepter(RendezVous $rdv, EntityManagerInterface $em): Response
{
    $rdv->setStatut('CONFIRME');
    $em->flush();

    $this->addFlash('success', 'Rendez-vous confirmé. Le patient a été informé.');
    return $this->redirectToRoute('app_medecin_dashboard'); // Route vers la liste des demandes
}

#[Route('/medecin/rendezvous/{id}/refuser', name: 'app_medecin_rdv_refuser')]
public function refuser(RendezVous $rdv, EntityManagerInterface $em): Response
{
    $rdv->setStatut('REFUSE');
    
    // Optionnel : On libère le créneau si le médecin refuse
    if ($rdv->getDisponibilite()) {
        $rdv->getDisponibilite()->setEstReserve(false);
    }
    
    $em->flush();

    $this->addFlash('danger', 'Rendez-vous refusé.Le patient a été informé.');
    return $this->redirectToRoute('app_medecin_dashboard');
}




#[Route('/rendezvous/annuler/{id}', name: 'app_rendezvous_annuler', methods: ['POST', 'GET'])]
public function annuler(RendezVous $rdv, EntityManagerInterface $em): Response
{
    // Vérification de sécurité : seul le patient propriétaire peut annuler
    if ($rdv->getPatient() !== $this->getUser()) {
        throw $this->createAccessDeniedException("Vous ne pouvez pas annuler ce rendez-vous.");
    }

    $medecinId = $rdv->getMedecin()->getId();

    // On supprime simplement la demande
    $em->remove($rdv);
    $em->flush();

    $this->addFlash('success', 'Votre demande de rendez-vous a été annulée.');

    // On redirige vers la page du calendrier du médecin pour voir le bouton "Réserver" revenir
    return $this->redirectToRoute('app_rendezvous_dispo', ['id' => $medecinId]);
}


#[Route('/mes-rendezvous', name: 'app_mes_rendezvous')]
    public function mesRendezVous(UserRepository $userRepo): Response
    {
        // On récupère l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour voir vos rendez-vous.');
            return $this->redirectToRoute('app_login');
        }

        // Sécurité : on s'assure que c'est un patient
        if ($user->getType() !== 'PATIENT') {
            $this->addFlash('warning', 'Cette page est réservée aux patients.');
            return $this->redirectToRoute('app_home');
        }

        // On récupère ses rendez-vous (si la relation est bien configurée dans l'entité User)
        $rendezvous = $user->getRendezVousPatient();

        return $this->render('rendezvous/mes_rendezvous.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }
}