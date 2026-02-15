<?php

namespace App\Controller\Front;

use App\Entity\ParticipantEvenement;
use App\Form\ModifierMaReservationType;
use App\Repository\ParticipantEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Front Office : Mes Réservations (réservé aux utilisateurs connectés).
 */
#[Route('/mes-reservations')]
class MesReservationsController extends AbstractController
{
    #[Route('', name: 'app_mes_reservations_index', methods: ['GET'])]
    public function index(ParticipantEvenementRepository $participantRepo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Connectez-vous pour voir vos réservations.');
            return $this->redirectToRoute('app_evenement_public');
        }

        $reservations = $participantRepo->findByUser($user);

        return $this->render('front/mes_reservations/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_mes_reservations_modifier', methods: ['GET', 'POST'])]
    public function modifier(Request $request, ParticipantEvenement $participantEvenement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_evenement_public');
        }

        if (!$this->isOwnReservation($participantEvenement, $user)) {
            $this->addFlash('error', 'Cette réservation ne vous appartient pas.');
            return $this->redirectToRoute('app_mes_reservations_index');
        }

        $form = $this->createForm(ModifierMaReservationType::class, $participantEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation mise à jour.');
            return $this->redirectToRoute('app_mes_reservations_index');
        }

        return $this->render('front/mes_reservations/modifier.html.twig', [
            'reservation' => $participantEvenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mes_reservations_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, ParticipantEvenement $participantEvenement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_evenement_public');
        }

        if (!$this->isOwnReservation($participantEvenement, $user)) {
            $this->addFlash('error', 'Cette réservation ne vous appartient pas.');
            return $this->redirectToRoute('app_mes_reservations_index');
        }

        if ($this->isCsrfTokenValid('delete'.$participantEvenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participantEvenement);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation annulée.');
        }

        return $this->redirectToRoute('app_mes_reservations_index', [], Response::HTTP_SEE_OTHER);
    }

    private function isOwnReservation(ParticipantEvenement $p, $user): bool
    {
        if ($p->getParticipant() && $p->getParticipant()->getId() === $user->getId()) {
            return true;
        }
        return $p->getEmail() !== null && $p->getEmail() === $user->getEmail();
    }
}
