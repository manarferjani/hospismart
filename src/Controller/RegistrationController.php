<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\User;
use App\Entity\Medecin;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ServiceRepository $serviceRepository,
        ValidatorInterface $validator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $genre = $request->request->get('genre');
            $dateNaissance = $request->request->get('date_naissance');
            $groupeSanguin = $request->request->get('groupe_sanguin');
            $adresse = $request->request->get('adresse');

            // Créer l'objet User
            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            // Valider l'objet User
            $validationErrors = $validator->validate($user);
            foreach ($validationErrors as $error) {
                $errors[] = $error->getMessage();
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
                
                // Ajouter groupe sanguin si rempli
                if ($groupeSanguin) {
                    $patient->setGroupeSanguin($groupeSanguin);
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
                    $this->addFlash('success', 'Compte créé avec succès. Connectez-vous.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        $services = $serviceRepository->findBy([], ['nom' => 'ASC']);
        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'services' => $services,
        ]);
    }
}
