<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

class FaceRecognitionController extends AbstractController
{
    /**
     * Endpoint AJAX : reçoit un descripteur facial (128 floats),
     * cherche l'utilisateur le plus proche et connecte via la sécurité Symfony.
     */
    #[Route('/api/face-login', name: 'api_face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {
        try {
            // Décoder le JSON
            $data = json_decode($request->getContent(), true);

            if (empty($data['descriptor']) || !is_array($data['descriptor'])) {
                return new JsonResponse(['success' => false, 'message' => 'Descripteur facial manquant.'], 400);
            }

            $inputDescriptor = array_values(array_map('floatval', $data['descriptor']));

            if (count($inputDescriptor) !== 128) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Descripteur invalide (' . count($inputDescriptor) . ' valeurs, 128 attendues).'
                ], 400);
            }

            // Récupérer tous les utilisateurs ayant un visage enregistré
            $allUsers = $userRepository->findUsersWithFaceDescriptor();

            if (empty($allUsers)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Aucun visage enregistré dans le système. Inscrivez-vous d\'abord et configurez votre visage.'
                ], 404);
            }

            $bestMatch = null;
            $bestDistance = PHP_FLOAT_MAX;
            $threshold = 0.55; // Légèrement plus tolérant

            foreach ($allUsers as $user) {
                $stored = $user->getFaceDescriptor();
                if (!$stored || count($stored) !== 128) {
                    continue;
                }
                $distance = $this->euclideanDistance($inputDescriptor, array_values($stored));
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = $user;
                }
            }

            if ($bestMatch === null || $bestDistance > $threshold) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Visage non reconnu. Utilisez email + mot de passe.',
                    'distance' => round($bestDistance, 4),
                ], 401);
            }

            // === AUTHENTIFICATION SYMFONY via Security Service ===
            // Connexion explicite sur le firewall "main"
            $security->login($bestMatch, null, 'main');

            // Redirection selon le rôle
            $roles = $bestMatch->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_MEDECIN', $roles, true)) {
                $redirectUrl = $this->generateUrl('app_dashboard');
            } else {
                // Vérifier si la route patient existe, sinon dashboard ou home
                try {
                    $redirectUrl = $this->generateUrl('app_patient_coordonnees');
                } catch (\Exception $e) {
                    $redirectUrl = $this->generateUrl('app_home'); // Fallback sûr
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Bienvenue ' . $bestMatch->getPrenom() . ' ' . $bestMatch->getNom() . ' !',
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')'
            ], 500);
        }
    }

    /**
     * Endpoint AJAX : enregistre/met à jour le descripteur facial de l'utilisateur connecté.
     */
    #[Route('/api/face-register', name: 'api_face_register', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function faceRegister(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['descriptor']) || !is_array($data['descriptor'])) {
            return new JsonResponse(['success' => false, 'message' => 'Descripteur facial manquant.'], 400);
        }

        $descriptor = array_values(array_map('floatval', $data['descriptor']));

        if (count($descriptor) !== 128) {
            return new JsonResponse(['success' => false, 'message' => 'Descripteur invalide.'], 400);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $user->setFaceDescriptor($descriptor);
        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre visage a été enregistré avec succès !',
        ]);
    }

    /**
     * Endpoint AJAX : supprime le descripteur facial de l'utilisateur connecté.
     */
    #[Route('/api/face-delete', name: 'api_face_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function faceDelete(EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $user->setFaceDescriptor(null);
        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Données biométriques supprimées.',
        ]);
    }

    /**
     * Page de configuration de la reconnaissance faciale (depuis le profil connecté).
     */
    #[Route('/face-setup', name: 'app_face_setup', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function faceSetup(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        return $this->render('security/face_setup.html.twig', [
            'user' => $user,
            'hasFace' => $user->hasFaceDescriptor(),
        ]);
    }

    /**
     * Calcule la distance euclidienne entre deux descripteurs 128D.
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $diff = ($a[$i] ?? 0.0) - ($b[$i] ?? 0.0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
}
