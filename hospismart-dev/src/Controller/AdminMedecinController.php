<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminMedecinType;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/medecin', name: 'app_admin_medecin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminMedecinController extends AbstractController
{
    #[Route(name: 'app_admin_medecin_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $medecins = $userRepository->findBy(['type' => 'MEDECIN']);
        
        return $this->render('admin_medecin/index.html.twig', [
            'medecins' => $medecins,
        ]);
    }

    #[Route('/new', name: 'app_admin_medecin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $medecin = new User();
        $medecin->setType('MEDECIN');
        $medecin->setRoles(['ROLE_MEDECIN']);
        
        $form = $this->createForm(AdminMedecinType::class, $medecin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $medecin->setPassword($passwordHasher->hashPassword($medecin, $plainPassword));
            } else {
                // Mot de passe par défaut si non fourni
                $medecin->setPassword($passwordHasher->hashPassword($medecin, 'Medecin@2024!'));
            }
            
            $entityManager->persist($medecin);
            $entityManager->flush();

            $this->addFlash('success', 'Médecin créé avec succès !');
            return $this->redirectToRoute('app_admin_medecin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_medecin/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_medecin_show', methods: ['GET'])]
    public function show(User $medecin): Response
    {
        if ($medecin->getType() !== 'MEDECIN') {
            throw $this->createNotFoundException('Médecin non trouvé');
        }

        return $this->render('admin_medecin/show.html.twig', [
            'medecin' => $medecin,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_medecin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $medecin, EntityManagerInterface $entityManager): Response
    {
        if ($medecin->getType() !== 'MEDECIN') {
            throw $this->createNotFoundException('Médecin non trouvé');
        }

        $form = $this->createForm(AdminMedecinType::class, $medecin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Médecin modifié avec succès !');
            return $this->redirectToRoute('app_admin_medecin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_medecin/edit.html.twig', [
            'medecin' => $medecin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_medecin_delete', methods: ['POST'])]
    public function delete(Request $request, User $medecin, EntityManagerInterface $entityManager): Response
    {
        if ($medecin->getType() !== 'MEDECIN') {
            throw $this->createNotFoundException('Médecin non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$medecin->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($medecin);
            $entityManager->flush();

            $this->addFlash('success', 'Médecin supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_medecin_index', [], Response::HTTP_SEE_OTHER);
    }
}
