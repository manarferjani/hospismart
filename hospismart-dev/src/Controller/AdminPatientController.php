<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminPatientType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/patient')]
#[IsGranted('ROLE_ADMIN')]
final class AdminPatientController extends AbstractController
{
    #[Route('', name: 'app_admin_patient_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $patients = $userRepository->findBy(['type' => 'PATIENT']);
        
        return $this->render('admin_patient/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/new', name: 'app_admin_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $patient = new User();
        $patient->setType('PATIENT');
        $patient->setRoles(['ROLE_USER']);
        
        $form = $this->createForm(AdminPatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($patient);
            $entityManager->flush();

            $this->addFlash('success', 'Patient créé avec succès !');
            return $this->redirectToRoute('app_admin_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_patient/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_patient_show', methods: ['GET'])]
    public function show(User $patient): Response
    {
        if ($patient->getType() !== 'PATIENT') {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        return $this->render('admin_patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_patient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $patient, EntityManagerInterface $entityManager): Response
    {
        if ($patient->getType() !== 'PATIENT') {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        $form = $this->createForm(AdminPatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Patient modifié avec succès !');
            return $this->redirectToRoute('app_admin_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_patient/edit.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_patient_delete', methods: ['POST'])]
    public function delete(Request $request, User $patient, EntityManagerInterface $entityManager): Response
    {
        if ($patient->getType() !== 'PATIENT') {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$patient->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();

            $this->addFlash('success', 'Patient supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_patient_index', [], Response::HTTP_SEE_OTHER);
    }
}
