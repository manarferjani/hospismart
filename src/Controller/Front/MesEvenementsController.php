<?php

namespace App\Controller\Front;

use App\Repository\ParticipantEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-evenements')]
#[IsGranted('ROLE_USER')]
class MesEvenementsController extends AbstractController
{
    #[Route('', name: 'app_mes_evenements', methods: ['GET'])]
    public function index(ParticipantEvenementRepository $participantRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $participations = $participantRepository->findBy(['participant' => $user], ['id' => 'DESC']);

        return $this->render('front/evenement/mes_evenements.html.twig', [
            'participations' => $participations,
        ]);
    }

    #[Route('/annuler/{id}', name: 'app_mes_evenements_annuler', methods: ['POST'])]
    public function annuler(int $id, ParticipantEvenementRepository $participantRepository, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        $participation = $participantRepository->find($id);

        if (!$participation || $participation->getParticipant() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette participation.');
        }

        if ($this->isCsrfTokenValid('annuler'.$participation->getId(), $request->request->get('_token'))) {
            $em->remove($participation);
            $em->flush();
            $this->addFlash('success', 'Votre participation a été annulée.');
        }

        return $this->redirectToRoute('app_mes_evenements');
    }
}
