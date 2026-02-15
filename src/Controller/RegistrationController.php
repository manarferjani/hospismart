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
        $isJson = $this->isJsonRequest($request);

        if ($request->isMethod('POST')) {
            $data = $this->getRequestData($request);
            
            $nom = $data['nom'] ?? null;
            $prenom = $data['prenom'] ?? null;
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            $genre = $data['genre'] ?? null;
            $dateNaissance = $data['date_naissance'] ?? null;
            $groupeSanguin = $data['groupe_sanguin'] ?? null;
            $adresse = $data['adresse'] ?? null;

            if (!$nom || !$prenom || !$email || !$password) {
                $errors[] = 'Les champs nom, prénom, email et mot de passe sont obligatoires.';
            }

            if (empty($errors)) {
                $user = new User();
                $user->setNom((string) $nom);
                $user->setPrenom((string) $prenom);
                $user->setEmail((string) $email);
                $user->setPassword($passwordHasher->hashPassword($user, (string) $password));
                
                // Logique unifiée dans User
                $user->setRoles(['ROLE_PATIENT']);
                $user->setType('PATIENT');
                $user->setGenre($genre ?: 'Autre');

                if ($dateNaissance) {
                    $user->setDateNaissance(new \DateTime($dateNaissance));
                } else {
                    $user->setDateNaissance((new \DateTime())->modify('-18 years'));
                }

                if ($groupeSanguin) {
                    $user->setGroupeSanguin(strtoupper(trim($groupeSanguin)));
                }

                if ($adresse) {
                    $user->setAdresse($adresse);
                }

                // Vérification email unique
                $userRepo = $entityManager->getRepository(User::class);
                if ($userRepo->findOneBy(['email' => $email])) {
                    $errors[] = 'Un compte existe déjà avec cet email.';
                }

                // Validation finale
                $validationErrors = $validator->validate($user);
                foreach ($validationErrors as $error) {
                    $errors[] = $error->getMessage();
                }

                if (empty($errors)) {
                    $entityManager->persist($user);
                    $entityManager->flush();

                    if ($isJson) {
                        return new JsonResponse(['success' => true, 'message' => 'Compte créé.'], Response::HTTP_CREATED);
                    }
                    $this->addFlash('success', 'Compte créé avec succès. Connectez-vous.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        if ($isJson && $request->isMethod('POST')) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'services' => $serviceRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    private function isJsonRequest(Request $request): bool {
        return str_contains($request->headers->get('Content-Type', ''), 'application/json');
    }

    private function getRequestData(Request $request): array {
        if ($this->isJsonRequest($request)) {
            return json_decode($request->getContent(), true) ?? [];
        }
        return $request->request->all();
    }
}