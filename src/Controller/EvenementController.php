<?php
namespace App\Controller;
use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EvenementController extends AbstractController
{
    #[Route('/', name: 'app_front_accueil', methods: ['GET'])]
    public function accueil(): Response { return $this->render('front/accueil.html.twig'); }

    #[Route('/evenement', name: 'app_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository, PaginatorInterface $paginator): Response {
        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');
        $queryBuilder = $evenementRepository->searchQueryBuilder($searchTerm, $type, $statut);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            4
        );
        return $this->render('evenement/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'typeFilter' => $type,
            'statutFilter' => $statut,
        ]);
    }

    #[Route('/evenement/public', name: 'app_evenement_public', methods: ['GET'])]
    public function public(EvenementRepository $evenementRepository): Response {
        return $this->render('front/evenements.html.twig', ['evenements' => $evenementRepository->findProchainsEvenements(20)]);
    }

    #[Route('/evenement/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'google_maps_api_key' => $this->getParameter('google_maps_api_key'),
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'google_maps_api_key' => $this->getParameter('google_maps_api_key'),
        ]);
    }

    #[Route('/evenement/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'google_maps_api_key' => $this->getParameter('google_maps_api_key'),
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'Evenement supprime.');
        }
        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/evenement/{id}/participants', name: 'app_evenement_participants', methods: ['GET'])]
    public function participants(Evenement $evenement): Response {
        return $this->render('evenement/participants.html.twig', [
            'evenement' => $evenement,
            'participants' => $evenement->getParticipants(),
        ]);
    }
}