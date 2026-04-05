<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/evenements')]
class EvenementApiController extends AbstractController
{
    #[Route('/prochains', name: 'api_evenements_prochains', methods: ['GET'])]
    public function getProchainsEvenements(EvenementRepository $evenementRepository): JsonResponse
    {
        $evenements = $evenementRepository->findProchainsEvenements(10);
        
        $data = array_map(function(Evenement $evenement) {
            return [
                'id' => $evenement->getId(),
                'titre' => $evenement->getTitre(),
                'description' => $evenement->getDescription(),
                'type' => $evenement->getTypeEvenement(),
                'date_debut' => $evenement->getDateDebut() ? $evenement->getDateDebut()->format('Y-m-d H:i:s') : null,
                'date_fin' => $evenement->getDateFin() ? $evenement->getDateFin()->format('Y-m-d H:i:s') : null,
                'lieu' => $evenement->getLieu(),
                'statut' => $evenement->getStatut(),
                'budget' => $evenement->getBudgetAlloue(),
                'nombre_participants' => $evenement->getParticipants()->count(),
            ];
        }, $evenements);

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/{id}', name: 'api_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): JsonResponse
    {
        $participants = [];
        foreach ($evenement->getParticipants() as $participant) {
            $participants[] = [
                'id' => $participant->getId(),
                'nom' => $participant->getNom(),
                'prenom' => $participant->getPrenom(),
                'email' => $participant->getEmail(),
                'telephone' => $participant->getTelephone(),
                'role' => $participant->getRole(),
                'confirme_presence' => $participant->isConfirmePresence(),
                'date_confirmation' => $participant->getDateConfirmation() ? $participant->getDateConfirmation()->format('Y-m-d H:i:s') : null,
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $evenement->getId(),
                'titre' => $evenement->getTitre(),
                'description' => $evenement->getDescription(),
                'type' => $evenement->getTypeEvenement(),
                'date_debut' => $evenement->getDateDebut() ? $evenement->getDateDebut()->format('Y-m-d H:i:s') : null,
                'date_fin' => $evenement->getDateFin() ? $evenement->getDateFin()->format('Y-m-d H:i:s') : null,
                'lieu' => $evenement->getLieu(),
                'statut' => $evenement->getStatut(),
                'budget' => $evenement->getBudgetAlloue(),
                'participants' => $participants,
            ]
        ]);
    }

    #[Route('', name: 'api_evenements_list', methods: ['GET'])]
    public function list(Request $request, EvenementRepository $evenementRepository): JsonResponse
    {
        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');

        $evenements = $evenementRepository->search($searchTerm, $type, $statut);
        
        $data = array_map(function(Evenement $evenement) {
            return [
                'id' => $evenement->getId(),
                'titre' => $evenement->getTitre(),
                'type' => $evenement->getTypeEvenement(),
                'date_debut' => $evenement->getDateDebut() ? $evenement->getDateDebut()->format('Y-m-d H:i:s') : null,
                'date_fin' => $evenement->getDateFin() ? $evenement->getDateFin()->format('Y-m-d H:i:s') : null,
                'lieu' => $evenement->getLieu(),
                'statut' => $evenement->getStatut(),
            ];
        }, $evenements);

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }
}
