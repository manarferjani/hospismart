<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rendezvous')]
#[IsGranted('ROLE_ADMIN')]
final class AdminRendezVousController extends AbstractController
{
    #[Route('', name: 'app_admin_rendezvous_index', methods: ['GET'])]
    public function index(RendezVousRepository $rdvRepository): Response
    {
        $rendezvous = $rdvRepository->findBy([], ['datetime' => 'DESC']);
        
        return $this->render('admin_rendezvous/index.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_rendezvous_show', methods: ['GET'])]
    public function show(RendezVous $rdv): Response
    {
        return $this->render('admin_rendezvous/show.html.twig', [
            'rendezvous' => $rdv,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_rendezvous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rdv, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous modifié avec succès !');
            return $this->redirectToRoute('app_admin_rendezvous_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_rendezvous/edit.html.twig', [
            'rendezvous' => $rdv,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_rendezvous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rdv, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rdv->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rdv);
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_rendezvous_index', [], Response::HTTP_SEE_OTHER);
    }
}
