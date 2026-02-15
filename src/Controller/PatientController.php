<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Service;
use App\Form\PatientType;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/patient')]
final class PatientController extends AbstractController
{
    /**
     * LISTE DES PATIENTS
     */
    #[Route(name: 'app_patient_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('patient/index.html.twig', [
            // On filtre par type 'PATIENT' depuis le UserRepository
            'patients' => $userRepository->findBy(['type' => 'PATIENT']),
        ]);
    }

    /**
     * NOUVEAU PATIENT
     */
    #[Route('/new', name: 'app_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $patient = new User();
        $patient->setType('PATIENT');
        
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($patient);
            $entityManager->flush();

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('patient/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    /**
     * RECHERCHE MÉDECIN (Côté Patient)
     * PLACÉE AVANT /{id} POUR ÉVITER LE CONFLIT
     */
    #[Route('/recherche-patient', name: 'app_medecin_recherche', methods: ['GET'])]
    public function recherche(UserRepository $userRepo, ServiceRepository $serviceRepo, Request $request): Response
    {
        $nomRecherche = $request->query->get('nom');

        if ($nomRecherche) {
            $medecins = $userRepo->createQueryBuilder('u')
                ->where('u.type = :type')
                ->andWhere('u.nom LIKE :nom OR u.prenom LIKE :nom OR u.specialite LIKE :nom')
                ->setParameter('type', 'MEDECIN')
                ->setParameter('nom', '%'.$nomRecherche.'%')
                ->getQuery()
                ->getResult();
            
            if (count($medecins) === 1) {
                return $this->redirectToRoute('app_medecin_show', ['id' => $medecins[0]->getId()]);
            }
        } else {
            $medecins = $userRepo->findBy(['type' => 'MEDECIN']);
        }

        return $this->render('patient/recherche_medecin.html.twig', [
            'medecins' => $medecins,
            'services' => $serviceRepo->findAll(),
        ]);
    }

    /**
     * MES COORDONNÉES
     */
    #[Route('/mes-coordonnees', name: 'app_patient_coordonnees', methods: ['GET'])]
    public function coordonnees(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        return $this->render('front/patient_coordonnees.html.twig', [
            'patient' => $user,
            'user' => $user,
        ]);
    }
    /**
     * VOIR UN PATIENT
     * AJOUT D'UNE RESTRICTION (id doit être un nombre)
     */
    #[Route('/{id}', name: 'app_patient_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $patient): Response
    {
        if ($patient->getType() !== 'PATIENT') {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    /**
     * MODIFIER UN PATIENT
     */
    #[Route('/{id}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $patient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('patient/edit.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    /**
     * SUPPRIMER UN PATIENT
     */
    #[Route('/{id}', name: 'app_patient_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $patient, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$patient->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
    }
}