<?php

namespace App\Controller;

use App\Repository\PatientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatientController extends AbstractController
{
    #[Route('/mes-coordonnees', name: 'app_patient_coordonnees', methods: ['GET'])]
    public function coordonnees(PatientRepository $patientRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $patient = $patientRepository->findOneByUser($user);

        return $this->render('front/patient_coordonnees.html.twig', [
            'patient' => $patient,
            'user' => $user,
        ]);
    }
}
