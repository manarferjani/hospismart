<?php

namespace App\Controller;

use App\Entity\ParametreVital;
use App\Form\ParametreVitalType;
use App\Repository\ParametreVitalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parametre-vital', name: 'app_parametre_vital')]
#[IsGranted('ROLE_ADMIN')]
final class ParametreVitalController extends AbstractController
{
    #[Route(name: 'app_parametre_vital_index', methods: ['GET'])]
    public function index(ParametreVitalRepository $parametreVitalRepository): Response
    {
        return $this->render('parametre_vital/index.html.twig', [
            'parametres_vitaux' => $parametreVitalRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_parametre_vital_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parametreVital = new ParametreVital();
        $form = $this->createForm(ParametreVitalType::class, $parametreVital);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parametreVital);
            $entityManager->flush();

            $this->addFlash('success', 'Paramètre vital créé avec succès !');
            return $this->redirectToRoute('app_parametre_vital_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parametre_vital/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_parametre_vital_show', methods: ['GET'])]
    public function show(ParametreVital $parametreVital): Response
    {
        return $this->render('parametre_vital/show.html.twig', [
            'parametre_vital' => $parametreVital,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parametre_vital_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ParametreVital $parametreVital, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParametreVitalType::class, $parametreVital);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Paramètre vital modifié avec succès !');
            return $this->redirectToRoute('app_parametre_vital_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parametre_vital/edit.html.twig', [
            'parametre_vital' => $parametreVital,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_parametre_vital_delete', methods: ['POST'])]
    public function delete(Request $request, ParametreVital $parametreVital, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$parametreVital->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($parametreVital);
            $entityManager->flush();

            $this->addFlash('success', 'Paramètre vital supprimé avec succès !');
        }

        return $this->redirectToRoute('app_parametre_vital_index', [], Response::HTTP_SEE_OTHER);
    }
}
