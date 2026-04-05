<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ServiceRepository $serviceRepository,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $errors = [];
        $debugInfo = [];

        if ($request->isMethod('POST')) {
            // Récupérer TOUTES les données POST brutes
            $allData = $request->request->all();
            $debugInfo['post_keys'] = array_keys($allData);

            $nom           = trim($allData['nom'] ?? '');
            $prenom        = trim($allData['prenom'] ?? '');
            $email         = trim($allData['email'] ?? '');
            $password      = $allData['password'] ?? '';
            $genre         = $allData['genre'] ?? 'Autre';
            $dateNaissance = $allData['date_naissance'] ?? null;
            $groupeSanguin = trim($allData['groupe_sanguin'] ?? '');
            $adresse       = trim($allData['adresse'] ?? '');
            $csrfToken     = $allData['_csrf_token'] ?? '';

            $debugInfo['nom']    = $nom;
            $debugInfo['prenom'] = $prenom;
            $debugInfo['email']  = $email;
            $debugInfo['csrf_present'] = !empty($csrfToken);
            $debugInfo['csrf_valid']   = $this->isCsrfTokenValid('register', $csrfToken);

            // Vérification CSRF
            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                $errors[] = 'Token CSRF invalide – veuillez recharger la page (F5) et réessayer.';
            }

            // Validations de base
            if (!$nom)    { $errors[] = 'Le nom est obligatoire.'; }
            if (!$prenom) { $errors[] = 'Le prénom est obligatoire.'; }
            if (!$email)  { $errors[] = 'L\'email est obligatoire.'; }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide : ' . $email; }
            if (!$password)           { $errors[] = 'Le mot de passe est obligatoire.'; }
            elseif (strlen($password) < 6) { $errors[] = 'Le mot de passe doit avoir au moins 6 caractères.'; }

            if (empty($errors)) {
                // Vérification email unique
                $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $errors[] = 'Un compte existe déjà avec l\'email : ' . $email;
                }
            }

            if (empty($errors)) {
                $user = new User();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setRoles(['ROLE_PATIENT']);
                $user->setType('PATIENT');
                $user->setGenre($genre ?: 'Autre');

                if ($dateNaissance) {
                    try {
                        $user->setDateNaissance(new \DateTime($dateNaissance));
                    } catch (\Exception $e) {
                        $user->setDateNaissance((new \DateTime())->modify('-18 years'));
                    }
                } else {
                    $user->setDateNaissance((new \DateTime())->modify('-18 years'));
                }

                if ($groupeSanguin) {
                    $user->setGroupeSanguin(strtoupper(substr($groupeSanguin, 0, 5)));
                }
                if ($adresse) {
                    $user->setAdresse($adresse);
                }

                // Validation Symfony entity
                $validationErrors = $validator->validate($user);
                foreach ($validationErrors as $err) {
                    $errors[] = '[Validation] ' . $err->getPropertyPath() . ': ' . $err->getMessage();
                }

                if (empty($errors)) {
                    try {
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash('success', 'Compte créé avec succès ! Connectez-vous.');
                        return $this->redirectToRoute('app_login');
                    } catch (\Exception $e) {
                        $errors[] = 'Erreur base de données : ' . $e->getMessage();
                        $logger->error('Erreur création compte: ' . $e->getMessage());
                    }
                }
            }

            $debugInfo['errors'] = $errors;
        }

        return $this->render('security/register.html.twig', [
            'errors'    => $errors,
            'debugInfo' => $debugInfo,
            'services'  => $serviceRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }
}