<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ParticipantEvenement;
use App\Entity\User;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EvenementController extends AbstractController
{
    /**
     * PAGE D'ACCUEIL FRONT
     */
    #[Route('/', name: 'app_front_accueil', methods: ['GET'])]
    public function accueil(): Response
    {
        return $this->render('front/accueil.html.twig');
    }

    /**
     * LISTE DES ÉVÉNEMENTS CÔTÉ PUBLIC
     * Placé avant la route {id} pour éviter les conflits
     */
    #[Route('/evenement/public', name: 'app_evenement_public', methods: ['GET'])]
    public function public(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findAll();

        return $this->render('front/evenements.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    /**
     * ACTION D'INSCRIPTION
     */
    #[Route('/evenement/public/{id}/inscription', name: 'app_evenement_inscription', methods: ['GET', 'POST'])]
    public function inscription(Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour vous inscrire.');
            return $this->redirectToRoute('app_login');
        }

        // 1. Vérifier si l'utilisateur est déjà inscrit
        $dejaInscrit = $entityManager->getRepository(ParticipantEvenement::class)->findOneBy([
            'evenement' => $evenement,
            'participant' => $user
        ]);

        if ($dejaInscrit) {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('app_evenement_public');
        }

        // 2. Création de la participation (via table intermédiaire)
        $participation = new ParticipantEvenement();
        $participation->setEvenement($evenement);
        $participation->setParticipant($user);
        
        // Remplissage avec les données de l'entité User
        $participation->setNom($user->getNom() ?? 'Nom'); 
        $participation->setPrenom($user->getPrenom() ?? 'Prénom');
        $participation->setEmail($user->getEmail());
        $participation->setTelephone($user->getTelephone());
        $participation->setRole('participant');
        $participation->setConfirmePresence(true);

        try {
            $entityManager->persist($participation);
            $entityManager->flush();
            $this->addFlash('success', 'Félicitations ! Votre inscription à l\'événement "' . $evenement->getTitre() . '" est confirmée.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_evenement_public');
    }

    /**
     * GESTION BACK-OFFICE (LISTE)
     */
    #[Route('/evenement', name: 'app_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');

        $evenements = $evenementRepository->search($searchTerm, $type, $statut);

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'searchTerm' => $searchTerm,
            'typeFilter' => $type,
            'statutFilter' => $statut,
        ]);
    }

    /**
     * CRÉER UN ÉVÉNEMENT
     */
    #[Route('/evenement/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    /**
     * VOIR UN ÉVÉNEMENT (Générique - à laisser vers la fin)
     */
    #[Route('/evenement/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    /**
     * MODIFIER UN ÉVÉNEMENT
     */
    #[Route('/evenement/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    /**
     * SUPPRIMER UN ÉVÉNEMENT
     */
    #[Route('/evenement/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
}