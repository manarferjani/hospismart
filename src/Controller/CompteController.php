<?php

namespace App\Controller;

use App\Repository\MedecinRepository;
use App\Repository\PatientRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/mon-compte')]
class CompteController extends AbstractController
{
    #[Route('', name: 'app_compte_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        PatientRepository $patientRepository,
        MedecinRepository $medecinRepository,
        ServiceRepository $serviceRepository,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $patient = $patientRepository->findOneByUser($user);
        $medecin = $medecinRepository->findOneByUser($user);
        $errors = [];

        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom', $user->getNom()));
            $user->setPrenom($request->request->get('prenom', $user->getPrenom()));
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setTelephone($request->request->get('telephone') ?: null);

            $newPassword = $request->request->get('password');
            if ($newPassword !== null && $newPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            if ($patient) {
                $patient->setGenre($request->request->get('genre', $patient->getGenre()));
                $dateNaiss = $request->request->get('date_naissance');
                if ($dateNaiss) {
                    $patient->setDateNaissance(new \DateTime($dateNaiss));
                }
                $patient->setGroupeSanguin($request->request->get('groupe_sanguin'));
                $patient->setAdresse($request->request->get('adresse'));
            }

            if ($medecin) {
                $user->setSpecialite($request->request->get('specialite', $user->getSpecialite()));
                $user->setMatricule($request->request->get('matricule', $user->getMatricule()));
                $user->setTelephone($request->request->get('medecin_telephone', $user->getTelephone()));
                $serviceId = $request->request->get('service');
                if ($serviceId) {
                    $service = $serviceRepository->find($serviceId);
                    if ($service) {
                        $user->setService($service);
                    }
                }
            }

            // Valider l'entité User
            $fieldErrors = [];
            $userErrors = $validator->validate($user);
            foreach ($userErrors as $error) {
                $propertyPath = $error->getPropertyPath();
                if (!isset($fieldErrors[$propertyPath])) {
                    $fieldErrors[$propertyPath] = [];
                }
                $fieldErrors[$propertyPath][] = $error->getMessage();
            }

            // Valider l'entité Patient si elle existe
            if ($patient) {
                $patientErrors = $validator->validate($patient);
                foreach ($patientErrors as $error) {
                    $propertyPath = $error->getPropertyPath();
                    if (!isset($fieldErrors[$propertyPath])) {
                        $fieldErrors[$propertyPath] = [];
                    }
                    $fieldErrors[$propertyPath][] = $error->getMessage();
                }
            }

            // Valider l'entité Medecin si elle existe
            if ($medecin) {
                $medecinErrors = $validator->validate($medecin);
                foreach ($medecinErrors as $error) {
                    $propertyPath = $error->getPropertyPath();
                    if (!isset($fieldErrors[$propertyPath])) {
                        $fieldErrors[$propertyPath] = [];
                    }
                    $fieldErrors[$propertyPath][] = $error->getMessage();
                }
            }

            // Convertir les erreurs de champs pour le template
            foreach ($fieldErrors as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = $message;
                }
            }

            if (empty($fieldErrors)) {
                // S'assurer que Doctrine track les entités modifiées
                $em->persist($user);
                if ($patient) {
                    $em->persist($patient);
                }
                if ($medecin) {
                    $em->persist($medecin);
                }
                $em->flush();
                $this->addFlash('success', 'Votre compte a été mis à jour.');
                if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MEDECIN')) {
                    return $this->redirectToRoute('app_dashboard');
                }
                return $this->redirectToRoute('app_patient_coordonnees');
            }
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        $isBack = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MEDECIN');
        return $this->render($isBack ? 'back/compte/edit.html.twig' : 'front/compte/edit.html.twig', [
            'user' => $user,
            'patient' => $patient,
            'medecin' => $medecin,
            'services' => $services,
            'errors' => $errors,
        ]);
    }
}
