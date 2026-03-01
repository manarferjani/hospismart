<?php

namespace App\Controller;

use App\Entity\MouvementStock;
use App\Form\MouvementStockType;
use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mouvement/stock')]
final class MouvementStockController extends AbstractController
{
    /**
     * Met à jour la quantité du médicament selon le mouvement (entrée = +, sortie = -).
     */
    private function appliquerMouvementSurStock(MouvementStock $mouvementStock): void
    {
        $medicament = $mouvementStock->getMedicament();
        if (!$medicament) {
            return;
        }
        $qte = $mouvementStock->getQuantite();
        $nouvelleQte = $medicament->getQuantite();
        if ($mouvementStock->getType() === 'ENTREE') {
            $nouvelleQte += $qte;
        } else {
            $nouvelleQte -= $qte;
        }
        $medicament->setQuantite(max(0, $nouvelleQte));
    }

    /**
     * Annule l'effet d'un mouvement sur le stock du médicament.
     */
    private function annulerMouvementSurStock(MouvementStock $mouvementStock): void
    {
        $medicament = $mouvementStock->getMedicament();
        if (!$medicament) {
            return;
        }
        $qte = $mouvementStock->getQuantite();
        $nouvelleQte = $medicament->getQuantite();
        if ($mouvementStock->getType() === 'ENTREE') {
            $nouvelleQte -= $qte;
        } else {
            $nouvelleQte += $qte;
        }
        $medicament->setQuantite(max(0, $nouvelleQte));
    }
    #[Route(name: 'app_mouvement_stock_index', methods: ['GET'])]
    public function index(MouvementStockRepository $mouvementStockRepository): Response
    {
        return $this->render('mouvement_stock/index.html.twig', [
            'mouvement_stocks' => $mouvementStockRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_mouvement_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MedicamentRepository $medicamentRepository): Response
    {
        $mouvementStock = new MouvementStock();
        $mouvementStock->setDateMouvement(new \DateTime());
        $medicamentId = $request->query->getInt('medicament');
        if ($medicamentId > 0) {
            $medicament = $medicamentRepository->find($medicamentId);
            if ($medicament) {
                $mouvementStock->setMedicament($medicament);
            }
        }
        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->appliquerMouvementSurStock($mouvementStock);
            $entityManager->persist($mouvementStock);
            $entityManager->flush();

            $medicamentNom = $mouvementStock->getMedicament() ? $mouvementStock->getMedicament()->getNom() : 'N/A';
            $this->addFlash('success', sprintf("Mouvement de stock pour '%s' créé avec succès.", $medicamentNom));

            return $this->redirectToRoute('app_mouvement_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mouvement_stock/new.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mouvement_stock_show', methods: ['GET'])]
    public function show(MouvementStock $mouvementStock): Response
    {
        return $this->render('mouvement_stock/show.html.twig', [
            'mouvement_stock' => $mouvementStock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mouvement_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager): Response
    {
        $ancienType = $mouvementStock->getType();
        $ancienneQuantite = $mouvementStock->getQuantite();

        $form = $this->createForm(MouvementStockType::class, $mouvementStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $medicament = $mouvementStock->getMedicament();
            if ($medicament) {
                // Annuler l'ancien mouvement
                $qteActuelle = $medicament->getQuantite();
                if ($ancienType === 'ENTREE') {
                    $qteActuelle -= $ancienneQuantite;
                } else {
                    $qteActuelle += $ancienneQuantite;
                }
                // Appliquer le nouveau mouvement
                if ($mouvementStock->getType() === 'ENTREE') {
                    $qteActuelle += $mouvementStock->getQuantite();
                } else {
                    $qteActuelle -= $mouvementStock->getQuantite();
                }
                $medicament->setQuantite(max(0, $qteActuelle));
            }
            $entityManager->flush();

            $medicamentNom = $mouvementStock->getMedicament() ? $mouvementStock->getMedicament()->getNom() : 'N/A';
            $this->addFlash('success', sprintf("Mouvement de stock pour '%s' modifié avec succès.", $medicamentNom));

            return $this->redirectToRoute('app_mouvement_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mouvement_stock/edit.html.twig', [
            'mouvement_stock' => $mouvementStock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mouvement_stock_delete', methods: ['POST'])]
    public function delete(Request $request, MouvementStock $mouvementStock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mouvementStock->getId(), $request->request->get('_token') ?? $request->get('_token'))) {
            $medicamentNom = $mouvementStock->getMedicament() ? $mouvementStock->getMedicament()->getNom() : 'N/A';
            $this->annulerMouvementSurStock($mouvementStock);
            $entityManager->remove($mouvementStock);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf("Mouvement de stock pour '%s' supprimé avec succès.", $medicamentNom));
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide — suppression annulée. Rechargez la page et réessayez.');
        }

        return $this->redirectToRoute('app_mouvement_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
