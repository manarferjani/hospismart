<?php

namespace App\Controller;

use App\Entity\ParticipantEvenement;
use App\Repository\ParticipantEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/evenement')]
class ParticipantEvenementController extends AbstractController
{
    #[Route('/', name: 'app_participant_evenement_index', methods: ['GET'])]
    public function index(ParticipantEvenementRepository $participantEvenementRepository): Response
    {
        return $this->render('participant_evenement/index.html.twig', [
            'participant_evenements' => $participantEvenementRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'app_participant_evenement_show', methods: ['GET'])]
    public function show(ParticipantEvenement $participantEvenement): Response
    {
        return $this->render('participant_evenement/show.html.twig', [
            'participant_evenement' => $participantEvenement,
        ]);
    }

    #[Route('/{id}', name: 'app_participant_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, ParticipantEvenement $participantEvenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participantEvenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participantEvenement);
            $entityManager->flush();
            $this->addFlash('success', 'Participant supprimé.');
        }

        return $this->redirectToRoute('app_participant_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
}
