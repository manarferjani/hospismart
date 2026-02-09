<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\User;
use App\Entity\Medecin;
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

            // Log registration data to CMD (no password)
            $logData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'genre' => $genre,
                'date_naissance' => $dateNaissance,
                'groupe_sanguin' => $groupeSanguin,
                'adresse' => $adresse ? '(présente)' : null,
            ];
            $logger->info('[Register] User registration attempt', $logData);
            error_log('[Register] ' . json_encode($logData, \JSON_UNESCAPED_UNICODE));

            // Données obligatoires manquantes (évite erreurs avec null)
            if (!$nom || !$prenom || !$email || !$password) {
                $errors[] = 'Les champs nom, prénom, email et mot de passe sont obligatoires.';
            }

            if (empty($errors)) {
                // Créer l'objet User
                $user = new User();
                $user->setNom((string) $nom);
                $user->setPrenom((string) $prenom);
                $user->setEmail((string) $email);
                $user->setPassword($passwordHasher->hashPassword($user, (string) $password));

                // Valider l'objet User
                $validationErrors = $validator->validate($user);
                foreach ($validationErrors as $error) {
                    $errors[] = $error->getMessage();
                }
            }

            if (empty($errors)) {
                // Vérifier si l'utilisateur existe déjà
                $userRepo = $entityManager->getRepository(User::class);
                if ($userRepo->findOneBy(['email' => $email])) {
                    $errors[] = 'Un compte existe déjà avec cet email.';
                }
                if ($userRepo->findOneBy(['nom' => $nom])) {
                    $errors[] = 'Ce nom d\'utilisateur est déjà utilisé.';
                }
            }

            if (empty($errors)) {
                // Rôle par défaut : patient
                $user->setRoles(['ROLE_PATIENT']);
                
                // Créer le patient
                $patient = new Patient();
                $patient->setUser($user);
                
                // Utiliser le genre fourni ou défaut
                $patient->setGenre($genre ?: 'Autre');
                
                // Utiliser la date fournie ou défaut (18 ans avant)
                if ($dateNaissance) {
                    $patient->setDateNaissance(new \DateTime($dateNaissance));
                } else {
                    $defaultDate = new \DateTime();
                    $defaultDate->modify('-18 years');
                    $patient->setDateNaissance($defaultDate);
                }
                
                // Ajouter groupe sanguin si rempli (normaliser en majuscules: o+ → O+)
                if ($groupeSanguin) {
                    $patient->setGroupeSanguin(strtoupper(trim($groupeSanguin)));
                }
                
                // Ajouter adresse si remplie
                if ($adresse) {
                    $patient->setAdresse($adresse);
                }

                // Valider le Patient
                $patientErrors = $validator->validate($patient);
                foreach ($patientErrors as $error) {
                    $errors[] = $error->getMessage();
                }

                if (empty($errors)) {
                    $entityManager->persist($user);
                    $entityManager->persist($patient);
                    $entityManager->flush();
                    if ($isJson) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Compte créé avec succès. Connectez-vous.',
                        ], Response::HTTP_CREATED);
                    }
                    $this->addFlash('success', 'Compte créé avec succès. Connectez-vous.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        if ($isJson && $request->isMethod('POST')) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'services' => $services,
        ]);
    }

    private function isJsonRequest(Request $request): bool
    {
        $contentType = $request->headers->get('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * Get request data from form (POST body) or JSON body.
     */
    private function getRequestData(Request $request): array
    {
        if ($this->isJsonRequest($request)) {
            $content = $request->getContent();
            if ($content === '') {
                return [];
            }
            $decoded = json_decode($content, true);
            return \is_array($decoded) ? $decoded : [];
        }
        return $request->request->all();
    }
}
