<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\RendezVous;
use App\Form\ConsultationType;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\AIService;
// Importations pour le PDF
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/consultation')]
final class ConsultationController extends AbstractController
{
#[Route(name: 'app_consultation_index', methods: ['GET'])]
public function index(ConsultationRepository $consultationRepository): Response
{
    return $this->render('consultation/index.html.twig', [
        // On remplace findAll() par notre nouvelle méthode triée
        'consultations' => $consultationRepository->findAllPrioritized(),
    ]);
}

#[Route('/new/{id}', name: 'app_consultation_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    EntityManagerInterface $entityManager, 
    AIService $aiService, // 1. Injecte ton nouveau service ici
    ?RendezVous $rendezVous = null
): Response {
    $consultation = new Consultation();
    /** @var \App\Entity\User|null $medecin */
    $medecin = $this->getUser();
    $consultation->setMedecin($medecin); 

    if ($rendezVous) {
        $consultation->setRendezVous($rendezVous);
        $consultation->setPatient($rendezVous->getPatient());
        $consultation->setMotif($rendezVous->getMotif());
    }

    $form = $this->createForm(ConsultationType::class, $consultation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        // 2. APPEL À L'IA : On analyse le motif pour obtenir le score
        $motif = $consultation->getMotif();
        if ($motif) {
            $score = $aiService->calculerPriorite($motif);
            $consultation->setPriorite($score); // On enregistre le 1, 2, 3, 4 ou 5
        }

        if ($rendezVous) {
            $rendezVous->setStatut('TERMINE'); 
        }

        $entityManager->persist($consultation);
        $entityManager->flush();

        $this->addFlash('success', 'Consultation enregistrée avec succès !');
        
        return $this->redirectToRoute('app_consultation_resume', ['id' => $consultation->getId()]);
    }

    return $this->render('medecin/consultation/new.html.twig', [
        'consultation' => $consultation,
        'form' => $form->createView(),
    ]);
}

    /**
     * Page de confirmation après enregistrement avec bouton d'impression
     */
    #[Route('/{id}/resume', name: 'app_consultation_resume', methods: ['GET'])]
    public function resume(Consultation $consultation): Response
    {
        return $this->render('medecin/consultation/resume.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    /**
     * Génération de l'ordonnance en PDF
     */
    #[Route('/{id}/pdf', name: 'app_consultation_pdf', methods: ['GET'])]
    public function generatePdf(Consultation $consultation): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('medecin/consultation/ordonnance_pdf.html.twig', [
            'consultation' => $consultation
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ordonnance_' . $consultation->getPatient()->getNom() . '.pdf"'
        ]);
    }

    #[Route('/{id}', name: 'app_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        return $this->render('consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_consultation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_consultation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('consultation/edit.html.twig', [
            'consultation' => $consultation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_consultation_delete', methods: ['POST'])]
    public function delete(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consultation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($consultation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_consultation_index', [], Response::HTTP_SEE_OTHER);
    }
}