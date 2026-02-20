<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\Disponibilite;
use App\Entity\User;
use App\Form\RendezVousType;
use App\Repository\UserRepository;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RendezVousController extends AbstractController
{
    #[Route('/rendezvous/dispo/{id}', name: 'app_rendezvous_dispo')]
    public function afficherDispos(int $id, UserRepository $userRepo, DisponibiliteRepository $dispoRepo): Response
    {
        $medecin = $userRepo->find($id);

        $dispos = $dispoRepo->findBy(
            ['medecin' => $medecin, 'est_reserve' => false],
            ['date_debut' => 'ASC']
        );

        return $this->render('rendezvous/calendrier.html.twig', [
            'medecin'        => $medecin,
            'disponibilites' => $dispos
        ]);
    }

    #[Route('/rendezvous/reserver/{id}', name: 'app_rendezvous_reserver')]
    public function reserver(Disponibilite $dispo, Request $request, EntityManagerInterface $em): Response
    {
        if ($dispo->isEstReserve()) {
            $this->addFlash('danger', 'Ce créneau est déjà réservé.');
            return $this->redirectToRoute('app_rendezvous_dispo', ['id' => $dispo->getMedecin()->getId()]);
        }

        $rdv = new RendezVous();
        $rdv->setMedecin($dispo->getMedecin());
        $rdv->setDatetime($dispo->getDateDebut());
        $rdv->setDisponibilite($dispo);
        $rdv->setStatut('EN_ATTENTE');

        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $rdv->setPatient($user);
        }

        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dispo->setEstReserve(true);
            $em->persist($rdv);
            $em->flush();

            $this->addFlash('success', 'Demande de rendez-vous envoyée au Dr. ' . $rdv->getMedecin()->getNom() . '. Vous serez notifié dès qu\'il aura accepté ou refusé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('rendezvous/finaliser.html.twig', [
            'form' => $form->createView(),
            'dispo' => $dispo
        ]);
    }

    #[Route('/rendezvous/annuler/{id}', name: 'app_rendezvous_annuler', methods: ['POST', 'GET'])]
    public function annuler(RendezVous $rdv, EntityManagerInterface $em): Response
    {
        $dispo = $rdv->getDisponibilite();
        if ($dispo) {
            $dispo->setEstReserve(false);
        }

        $em->remove($rdv);
        $em->flush();

        $this->addFlash('success', 'Votre rendez-vous a bien été annulé. Le créneau est à nouveau libre.');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/mes-rendezvous', name: 'app_mes_rendezvous')]
    public function mesRendezVous(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour voir vos rendez-vous.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getType() !== 'PATIENT') {
            $this->addFlash('warning', 'Cette page est réservée aux patients.');
            return $this->redirectToRoute('app_home');
        }

        $rendezvous = $user->getRendezVousPatient();

        return $this->render('rendezvous/mes_rendezvous.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }
}