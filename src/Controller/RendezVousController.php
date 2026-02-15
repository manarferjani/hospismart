<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\Disponibilite;
use App\Entity\User; // On utilise User à la place de Patient
use App\Form\RendezVousType;
use App\Repository\UserRepository; // Import du UserRepository
use App\Repository\DisponibiliteRepository;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RendezVousController extends AbstractController
{
#[Route('/rendezvous/dispo/{id}', name: 'app_rendezvous_dispo')]
public function afficherDispos(int $id, MedecinRepository $medecinRepo, DisponibiliteRepository $dispoRepo): Response 
{
    $medecin = $medecinRepo->find($id);

    $dispos = $dispoRepo->findBy(
        [
            'medecin' => $medecin, 
            'est_reserve' => false  
        ],
        ['date_debut' => 'ASC']
    );

    return $this->render('rendezvous/calendrier.html.twig', [
        'medecin' => $medecin,
        'disponibilites' => $dispos
    ]);
}

    #[Route('/rendezvous/reserver/{id}', name: 'app_rendezvous_reserver')]
    public function reserver(Disponibilite $dispo, Request $request, EntityManagerInterface $em): Response
    {
        // 1. Vérification de sécurité
        if ($dispo->isEstReserve()) {
            $this->addFlash('danger', 'Ce créneau est déjà réservé.');
            return $this->redirectToRoute('app_rendezvous_dispo', ['id' => $dispo->getMedecin()->getId()]);
        }

        $rdv = new RendezVous();
        
        // 2. Pré-remplissage automatique
        $rdv->setMedecin($dispo->getMedecin());
        $rdv->setDatetime($dispo->getDateDebut());
        $rdv->setDisponibilite($dispo);
        $rdv->setStatut('EN_ATTENTE');
        
        
        // On lie le patient connecté
        $user = $this->getUser();
        if ($user) {
            $rdv->setPatient($user); // On passe l'objet User directement
        }

        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 3. Logique de confirmation
            $dispo->setEstReserve(true);
            $em->persist($rdv);
            $em->flush();

            $this->addFlash('success', 'Demande de rendez-vous envoyée au Dr. ' . $rdv->getMedecin()->getUser()->getNom() . '. Vous serez notifié dès qu\'il aura accepté ou refusé.');
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
    // 1. Récupérer la disponibilité liée à ce rendez-vous
    $dispo = $rdv->getDisponibilite();

    if ($dispo) {
        // 2. Remettre le créneau comme disponible pour les autres patients
        $dispo->setEstReserve(false);
    }

    // 3. Supprimer le rendez-vous de la base de données
    // Note: Vous pouvez aussi faire $rdv->setStatut('ANNULE') si vous voulez garder une trace
    $em->remove($rdv);
    
    // 4. Sauvegarder les changements
    $em->flush();

    $this->addFlash('success', 'Votre rendez-vous a bien été annulé. Le créneau est à nouveau libre.');

    return $this->redirectToRoute('app_home'); // Ou vers la page "Mes Rendez-vous"
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