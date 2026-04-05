<?php

namespace App\Controller\Front;

use App\Entity\Evenement;
use App\Entity\ParticipantEvenement;
use App\Form\InscriptionParticipantType;
use App\Repository\ParticipantEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Front Office : formulaire d'inscription des participants.
 * Seul le participant peut s'inscrire via ce formulaire.
 */
#[Route('/evenement/public')]
class InscriptionController extends AbstractController
{
    #[Route('/{id}/inscription', name: 'app_evenement_inscription', methods: ['GET', 'POST'])]
    public function inscription(Request $request, Evenement $evenement, EntityManagerInterface $entityManager, ParticipantEvenementRepository $participantRepo): Response
    {
        $user = $this->getUser();
        $participantEvenement = new ParticipantEvenement();
        $participantEvenement->setEvenement($evenement);
        $participantEvenement->setRole('participant');
        $participantEvenement->setConfirmePresence(true);

        if ($user) {
            $participantEvenement->setNom($user->getNom());
            $participantEvenement->setPrenom($user->getPrenom());
            $participantEvenement->setEmail($user->getEmail());
            $participantEvenement->setParticipant($user);
            if (method_exists($user, 'getTelephone') && $user->getTelephone() !== null) {
                $participantEvenement->setTelephone((string) $user->getTelephone());
            }
        }

        $form = $this->createForm(InscriptionParticipantType::class, $participantEvenement, [
            'user_connected' => $user !== null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $participantEvenement->getEmail();
            $existing = $participantRepo->findOneBy([
                'evenement' => $evenement,
                'email' => $email,
            ]);

            if ($existing) {
                $this->addFlash('warning', 'Cet email est déjà inscrit à cet événement.');
                return $this->redirectToRoute('app_evenement_public');
            }

            $entityManager->persist($participantEvenement);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous participez à cet événement.');
            return $this->redirectToRoute('app_evenement_public');
        }

        return $this->render('front/inscription.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'user_connected' => $user !== null,
        ]);
    }
}
