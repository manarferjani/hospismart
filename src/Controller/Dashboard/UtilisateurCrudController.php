<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
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
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $email = trim((string) $request->request->get('email'));
            $telephone = $request->request->get('telephone') ? trim((string) $request->request->get('telephone')) : null;
            $password = trim((string) ($request->request->get('password') ?? ''));
            $role = $request->request->get('role', 'ROLE_PATIENT');
            
            // Récupération des champs spécifiques
            $genre = $request->request->get('genre');
            $dateNaissanceStr = $request->request->get('date_naissance');
            $groupeSanguin = $request->request->get('groupe_sanguin');
            $adresse = $request->request->get('adresse');
            $specialite = $request->request->get('specialite');
            $matricule = $request->request->get('matricule');
            $serviceId = $request->request->get('service');

            $formData = compact('nom', 'prenom', 'email', 'telephone', 'role', 'genre', 'dateNaissanceStr', 'groupeSanguin', 'adresse', 'specialite', 'matricule', 'serviceId');

            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setRoles([$role]);
            
            // Assignation des champs spécifiques (Tous sur User maintenant)
            $user->setGenre($genre);
            if ($dateNaissanceStr) {
                try {
                    $user->setDateNaissance(new \DateTime($dateNaissanceStr));
                } catch (\Exception $e) {}
            }
            $user->setGroupeSanguin($groupeSanguin);
            $user->setAdresse($adresse);
            
            $user->setSpecialite($specialite);
            $user->setMatricule($matricule);
            
            if ($role === 'ROLE_MEDECIN') {
                 // Gestion Service
                if ($serviceId) {
                    $service = $serviceRepository->find($serviceId);
                    if ($service) {
                        $user->setServiceEntity($service);
                    }
                } else {
                     // Si pas de service sélectionné pour un médecin, peut-être une erreur ?
                     // On laisse passer pour l'instant ou on met une erreur.
                }
                // Si matricule vide, on en génère un
                if (!$matricule) {
                    $user->setMatricule('MAT' . uniqid());
                }
                // Spécialité par défaut
                if (!$specialite) {
                    $user->setSpecialite('Généraliste');
                }
            }

            // Validation
            $userErrors = $validator->validate($user);
            foreach ($userErrors as $error) {
                $errors[] = $error->getMessage();
            }

            if (empty($errors)) {
                if ($userRepository->findOneBy(['email' => $email])) {
                    $errors[] = 'Un utilisateur existe déjà avec cet email.';
                }
            }

            if (empty($errors)) {
                try {
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success', 'Utilisateur créé avec succès.');
                    return $this->redirectToRoute('app_dashboard_utilisateurs_show', ['id' => $user->getId()]);
                } catch (\Exception $e) {
                    $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
                }
            }
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        return $this->render('back/utilisateurs/new.html.twig', [
            'errors' => $errors,
            'formData' => $formData,
            'services' => $services,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_utilisateurs_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user): Response
    {
        // On passe l'utilisateur user comme "patient" et "medecin" car les champs sont maintenant sur User.
        // Les templates utilisent patient.genre etc, qui sont dispos sur User.
        return $this->render('back/utilisateurs/show.html.twig', [
            'user' => $user,
            'patient' => $user, // Hack de compatibilité template
            'medecin' => $user, // Hack de compatibilité template
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_dashboard_utilisateurs_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        ServiceRepository $serviceRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        
        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom', $user->getNom()));
            $user->setPrenom($request->request->get('prenom', $user->getPrenom()));
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setTelephone($request->request->get('telephone') ?: null);

            $newPassword = $request->request->get('password');
            if ($newPassword !== null && $newPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            // Champs Patient
            $user->setGenre($request->request->get('genre', $user->getGenre()));
            if ($request->request->get('date_naissance')) {
                $user->setDateNaissance(new \DateTime($request->request->get('date_naissance')));
            }
            $user->setGroupeSanguin($request->request->get('groupe_sanguin', $user->getGroupeSanguin()));
            $user->setAdresse($request->request->get('adresse', $user->getAdresse()));

            // Champs Medecin
            $user->setSpecialite($request->request->get('specialite', $user->getSpecialite()));
            $user->setMatricule($request->request->get('matricule', $user->getMatricule()));
            // Le téléphone médecin (user_telephone dans le form) est le même que le user telephone
            if ($request->request->get('user_telephone')) {
                 $user->setTelephone($request->request->get('user_telephone'));
            }

            $serviceId = $request->request->get('service');
            if ($serviceId) {
                $service = $serviceRepository->find($serviceId);
                if ($service) {
                    $user->setServiceEntity($service);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_dashboard_utilisateurs_show', ['id' => $user->getId()]);
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        return $this->render('back/utilisateurs/edit.html.twig', [
            'user' => $user,
            'patient' => $user, // Compatibilité
            'medecin' => $user, // Compatibilité
            'services' => $services,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_dashboard_utilisateurs_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($user->getId() === $this->getUser()?->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_dashboard_utilisateurs_list');
        }
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), (string) $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('app_dashboard_utilisateurs_list');
    }
}
