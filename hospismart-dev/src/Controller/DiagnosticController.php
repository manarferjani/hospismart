<?php

namespace App\Controller;

use App\Entity\Diagnostic;
use App\Form\DiagnosticType;
use App\Repository\DiagnosticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/diagnostic', name: 'app_diagnostic')]
#[IsGranted('ROLE_ADMIN')]
final class DiagnosticController extends AbstractController
{
    #[Route(name: 'app_diagnostic_index', methods: ['GET'])]
    public function index(DiagnosticRepository $diagnosticRepository): Response
    {
        return $this->render('diagnostic/index.html.twig', [
            'diagnostics' => $diagnosticRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_diagnostic_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $diagnostic = new Diagnostic();
        $form = $this->createForm(DiagnosticType::class, $diagnostic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($diagnostic);
            $entityManager->flush();

            $this->addFlash('success', 'Diagnostic créé avec succès !');
            return $this->redirectToRoute('app_diagnostic_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('diagnostic/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_diagnostic_show', methods: ['GET'])]
    public function show(Diagnostic $diagnostic): Response
    {
        return $this->render('diagnostic/show.html.twig', [
            'diagnostic' => $diagnostic,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_diagnostic_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Diagnostic $diagnostic, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DiagnosticType::class, $diagnostic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Diagnostic modifié avec succès !');
            return $this->redirectToRoute('app_diagnostic_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('diagnostic/edit.html.twig', [
            'diagnostic' => $diagnostic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_diagnostic_delete', methods: ['POST'])]
    public function delete(Request $request, Diagnostic $diagnostic, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$diagnostic->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($diagnostic);
            $entityManager->flush();

            $this->addFlash('success', 'Diagnostic supprimé avec succès !');
        }

        return $this->redirectToRoute('app_diagnostic_index', [], Response::HTTP_SEE_OTHER);
    }
}
