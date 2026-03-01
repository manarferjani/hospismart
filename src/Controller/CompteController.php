<?php

namespace App\Controller;


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

        ServiceRepository $serviceRepository,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Identifier le type d'utilisateur pour adapter l'affichage/traitement
        $isPatient = in_array('ROLE_PATIENT', $user->getRoles()) || $user->getType() === 'PATIENT';
        $isMedecin = in_array('ROLE_MEDECIN', $user->getRoles()) || $user->getType() === 'MEDECIN';
        $errors = [];

        if ($request->isMethod('POST')) {
            // Champs communs
            $user->setNom($request->request->get('nom', $user->getNom()));
            $user->setPrenom($request->request->get('prenom', $user->getPrenom()));
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setTelephone($request->request->get('telephone') ?: null);

            // Mot de passe
            $newPassword = $request->request->get('password');
            if ($newPassword !== null && $newPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            // Champs Patient
            if ($isPatient) {
                $user->setGenre($request->request->get('genre', $user->getGenre()));
                $dateNaiss = $request->request->get('date_naissance');
                if ($dateNaiss) {
                    $user->setDateNaissance(new \DateTime($dateNaiss));
                }
                $user->setGroupeSanguin($request->request->get('groupe_sanguin'));
                $user->setAdresse($request->request->get('adresse'));
            }

            // Champs Médecin
            if ($isMedecin) {
                $user->setSpecialite($request->request->get('specialite', $user->getSpecialite()));
                $user->setMatricule($request->request->get('matricule', $user->getMatricule()));
                // Si le téléphone médecin est différent ou spécifique, adapter ici, sinon on utilise le commun
                if ($request->request->get('medecin_telephone')) {
                    $user->setTelephone($request->request->get('medecin_telephone'));
                }
                
                $serviceId = $request->request->get('service');
                if ($serviceId) {
                    $service = $serviceRepository->find($serviceId);
                    if ($service) {
                        $user->setServiceEntity($service);
                    }
                }
            }

            // Validation
            $userErrors = $validator->validate($user);
            if (count($userErrors) > 0) {
                foreach ($userErrors as $error) {
                    $errors[] = $error->getMessage();
                }
            }

            if (empty($errors)) {
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Votre compte a été mis à jour.');
                
                if ($this->isGranted('ROLE_ADMIN') || $isMedecin) {
                    return $this->redirectToRoute('app_dashboard');
                }
                // Redirection par défaut pour patient ou autre
                return $this->redirectToRoute('app_home'); 
            }
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        $isAdminOrMedecin = $this->isGranted('ROLE_ADMIN') || $isMedecin;

        return $this->render($isAdminOrMedecin ? 'back/compte/edit.html.twig' : 'front/compte/edit.html.twig', [
            'user' => $user,
            'isPatient' => $isPatient,
            'isMedecin' => $isMedecin,
            'services' => $services,
            'errors' => $errors,
        ]);
    }
}
