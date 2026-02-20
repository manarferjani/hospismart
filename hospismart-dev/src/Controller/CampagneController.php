<?php

namespace App\Controller;

use App\Entity\Campagne;
use App\Form\CampagneType;
use App\Repository\CampagneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/campagne', name: 'app_campagne')]
#[IsGranted('ROLE_ADMIN')]
final class CampagneController extends AbstractController
{
    #[Route(name: 'app_campagne_index', methods: ['GET'])]
    public function index(CampagneRepository $campagneRepository): Response
    {
        return $this->render('campagne/index.html.twig', [
            'campagnes' => $campagneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_campagne_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $campagne = new Campagne();
        $form = $this->createForm(CampagneType::class, $campagne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($campagne);
            $entityManager->flush();

            $this->addFlash('success', 'Campagne créée avec succès !');
            return $this->redirectToRoute('app_campagne_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('campagne/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_campagne_show', methods: ['GET'])]
    public function show(Campagne $campagne): Response
    {
        return $this->render('campagne/show.html.twig', [
            'campagne' => $campagne,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_campagne_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Campagne $campagne, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampagneType::class, $campagne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Campagne modifiée avec succès !');
            return $this->redirectToRoute('app_campagne_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('campagne/edit.html.twig', [
            'campagne' => $campagne,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_campagne_delete', methods: ['POST'])]
    public function delete(Request $request, Campagne $campagne, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$campagne->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($campagne);
            $entityManager->flush();

            $this->addFlash('success', 'Campagne supprimée avec succès !');
        }

        return $this->redirectToRoute('app_campagne_index', [], Response::HTTP_SEE_OTHER);
    }
}
