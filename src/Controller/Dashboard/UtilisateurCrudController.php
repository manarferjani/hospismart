<?php

namespace App\Controller\Dashboard;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\User;
use App\Repository\MedecinRepository;
use App\Repository\PatientRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/dashboard/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurCrudController extends AbstractController
{
    #[Route('', name: 'app_dashboard_utilisateurs_list', methods: ['GET'])]
    public function list(Request $request, UserRepository $userRepository): Response
    {
        $nom = $request->query->get('nom', '');
        $role = $request->query->get('role', '');
        $tri = $request->query->get('tri', 'nom_asc');
        $sortOrder = ($tri === 'nom_desc') ? 'DESC' : 'ASC';

        $users = $userRepository->findWithFilters(
            $nom !== '' ? $nom : null,
            $role !== '' ? $role : null,
            $sortOrder
        );

        return $this->render('back/utilisateurs/list.html.twig', [
            'users' => $users,
            'nom' => $nom,
            'role' => $role,
            'tri' => $tri,
        ]);
    }

    #[Route('/nouveau', name: 'app_dashboard_utilisateurs_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ServiceRepository $serviceRepository,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): Response {
        $errors = [];
        $formData = [];
        
        if ($request->isMethod('POST')) {
            error_log('DEBUG: POST request received');
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $email = trim((string) $request->request->get('email'));
            $telephone = $request->request->get('telephone') ? trim((string) $request->request->get('telephone')) : null;
            $password = trim((string) ($request->request->get('password') ?? ''));
            $role = $request->request->get('role', 'ROLE_PATIENT');
            $genre = $request->request->get('genre') ?: null;
            $dateNaissance = $request->request->get('date_naissance') ?: null;
            $groupeSanguin = $request->request->get('groupe_sanguin') ?: null;
            $adresse = $request->request->get('adresse') ?: null;
            $specialite = $request->request->get('specialite') ?: null;
            $matricule = $request->request->get('matricule') ?: null;

            error_log('DEBUG: nom=' . $nom . ', email=' . $email . ', role=' . $role . ', password_length=' . strlen($password));

            // Stocker les données pour le re-affichage du formulaire
            $formData = compact('nom', 'prenom', 'email', 'telephone', 'role', 'genre', 'dateNaissance', 'groupeSanguin', 'adresse', 'specialite', 'matricule');

            // Créer le User pour le valider
            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setRoles([$role]);  // Assignez le rôle AVANT la validation

            // Valider l'objet User
            $userErrors = $validator->validate($user);
            foreach ($userErrors as $error) {
                $errors[] = $error->getMessage();
            }
            error_log('DEBUG: User validation errors: ' . count($userErrors));

            if (empty($errors)) {
                // Vérifier les doublons
                if ($userRepository->findOneBy(['email' => $email])) {
                    $errors[] = 'Un utilisateur existe déjà avec cet email.';
                }
                if ($userRepository->findOneBy(['nom' => $nom])) {
                    $errors[] = 'Ce nom d\'utilisateur est déjà utilisé.';
                }
            }

            error_log('DEBUG: Errors after duplicate check: ' . count($errors));

            if (empty($errors)) {
                error_log('DEBUG: Processing role: ' . $role);

                // Gérer Patient
                if ($role === 'ROLE_PATIENT') {
                    error_log('DEBUG: Creating patient');
                    $patient = new Patient();
                    $patient->setUser($user);
                    $patient->setGenre($request->request->get('genre') ?: 'Autre');
                    
                    $dateNaiss = $request->request->get('date_naissance');
                    if ($dateNaiss) {
                        $patient->setDateNaissance(new \DateTime($dateNaiss));
                    } else {
                        $defaultDate = new \DateTime();
                        $defaultDate->modify('-18 years');
                        $patient->setDateNaissance($defaultDate);
                    }
                    $patient->setGroupeSanguin($request->request->get('groupe_sanguin') ?: null);
                    $patient->setAdresse($request->request->get('adresse') ?: null);

                    $patientErrors = $validator->validate($patient);
                    foreach ($patientErrors as $error) {
                        $errors[] = $error->getMessage();
                    }
                    
                    if (empty($errors)) {
                        try {
                            $em->persist($user);
                            $em->persist($patient);
                            $em->flush();
                            $this->addFlash('success', 'Patient créé avec succès.');
                            return $this->redirectToRoute('app_dashboard_utilisateurs_show', ['id' => $user->getId()]);
                        } catch (\Exception $e) {
                            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
                        }
                    }
                }
                // Gérer Médecin
                elseif ($role === 'ROLE_MEDECIN') {
                    error_log('DEBUG: Creating medecin');
                    $service = $serviceRepository->findOneBy([]);
                    if (!$service) {
                        error_log('DEBUG: No service found');
                        $errors[] = 'Aucun service disponible. Veuillez créer un service d\'abord.';
                    } else {
                        error_log('DEBUG: Service found, creating medecin object');
                        $medecin = new Medecin();
                        $medecin->setUser($user);
                        $medecin->setSpecialite($request->request->get('specialite') ?: 'Généraliste');
                        $medecin->setMatricule($request->request->get('matricule') ?: 'MAT' . uniqid());
                        $medecin->setTelephone($request->request->get('telephone') ? trim((string) $request->request->get('telephone')) : null);
                        $medecin->setService($service);

                        $medecinErrors = $validator->validate($medecin);
                        error_log('DEBUG: Medecin validation errors: ' . count($medecinErrors));
                        foreach ($medecinErrors as $error) {
                            $errors[] = $error->getMessage();
                        }
                        
                        if (empty($errors)) {
                            error_log('DEBUG: About to persist Medecin');
                            try {
                                $em->persist($user);
                                $em->persist($medecin);
                                $em->flush();
                                error_log('DEBUG: Medecin created successfully');
                                $this->addFlash('success', 'Médecin créé avec succès.');
                                return $this->redirectToRoute('app_dashboard_utilisateurs_show', ['id' => $user->getId()]);
                            } catch (\Exception $e) {
                                error_log('DEBUG: Exception on medecin flush: ' . $e->getMessage());
                                $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
                            }
                        }
                    }
                }
                // Gérer Admin
                else {
                    error_log('DEBUG: Creating admin');
                    try {
                        $em->persist($user);
                        $em->flush();
                        error_log('DEBUG: Admin created successfully');
                        $this->addFlash('success', 'Admin créé avec succès.');
                        return $this->redirectToRoute('app_dashboard_utilisateurs_show', ['id' => $user->getId()]);
                    } catch (\Exception $e) {
                        error_log('DEBUG: Exception on admin flush: ' . $e->getMessage());
                        $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
                    }
                }
            }
        }
        error_log('DEBUG: Rendering template with errors: ' . count($errors));

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        return $this->render('back/utilisateurs/new.html.twig', [
            'errors' => $errors,
            'formData' => $formData,
            'services' => $services,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_utilisateurs_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user, PatientRepository $patientRepository, MedecinRepository $medecinRepository): Response
    {
        $patient = $patientRepository->findOneByUser($user);
        $medecin = $medecinRepository->findOneByUser($user);

        return $this->render('back/utilisateurs/show.html.twig', [
            'user' => $user,
            'patient' => $patient,
            'medecin' => $medecin,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_dashboard_utilisateurs_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        PatientRepository $patientRepository,
        MedecinRepository $medecinRepository,
        ServiceRepository $serviceRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $patient = $patientRepository->findOneByUser($user);
        $medecin = $medecinRepository->findOneByUser($user);

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
                $patient->setDateNaissance($request->request->get('date_naissance') ? new \DateTime($request->request->get('date_naissance')) : $patient->getDateNaissance());
                $patient->setGroupeSanguin($request->request->get('groupe_sanguin'));
                $patient->setAdresse($request->request->get('adresse'));
            }

            if ($medecin) {
                $medecin->setSpecialite($request->request->get('specialite', $medecin->getSpecialite()));
                $medecin->setMatricule($request->request->get('matricule', $medecin->getMatricule()));
                $medecin->setTelephone($request->request->get('medecin_telephone', $medecin->getTelephone()));
                $serviceId = $request->request->get('service');
                if ($serviceId) {
                    $service = $serviceRepository->find($serviceId);
                    if ($service) {
                        $medecin->setService($service);
                    }
                }
            }

            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_dashboard_utilisateurs_show', ['id' => $user->getId()]);
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        return $this->render('back/utilisateurs/edit.html.twig', [
            'user' => $user,
            'patient' => $patient,
            'medecin' => $medecin,
            'services' => $services,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_dashboard_utilisateurs_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em, PatientRepository $patientRepository, MedecinRepository $medecinRepository): Response
    {
        if ($user->getId() === $this->getUser()?->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_dashboard_utilisateurs_list');
        }
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), (string) $request->request->get('_token'))) {
            $patient = $patientRepository->findOneByUser($user);
            $medecin = $medecinRepository->findOneByUser($user);
            if ($patient) {
                $em->remove($patient);
            }
            if ($medecin) {
                $em->remove($medecin);
            }
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('app_dashboard_utilisateurs_list');
    }
}
