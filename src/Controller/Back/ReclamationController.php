<?php

namespace App\Controller\Back;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reclamation')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'app_back_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('back/reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findBy([], ['dateCreation' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'app_back_reclamation_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $reponse->setAdminNom($user ? $user->getNom() . ' ' . $user->getPrenom() : 'Admin');
            $reponse->setAdminEmail($user ? $user->getEmail() : 'admin@hospismart.com');
            $reponse->setReclamation($reclamation);
            
            // Mise à jour du statut de la réclamation
            $reclamation->setStatut('Traité');

            $em->persist($reponse);
            $em->flush();

            $this->addFlash('success', 'Réponse ajoutée avec succès.');
            return $this->redirectToRoute('app_back_reclamation_show', ['id' => $reclamation->getId()]);
        }

        return $this->render('back/reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/response/{id}/edit', name: 'app_back_reponse_edit', methods: ['POST'])]
    public function editResponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        $content = $request->request->get('content');
        if ($content) {
            $reponse->setContenu($content);
            $em->flush();
            $this->addFlash('success', 'Réponse modifiée.');
        }
        return $this->redirectToRoute('app_back_reclamation_show', ['id' => $reponse->getReclamation()->getId()]);
    }

    #[Route('/response/{id}/delete', name: 'app_back_reponse_delete', methods: ['POST'])]
    public function deleteResponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $reclamationId = $reponse->getReclamation()->getId();
            $em->remove($reponse);
            $em->flush();
            $this->addFlash('success', 'Réponse supprimée.');
            return $this->redirectToRoute('app_back_reclamation_show', ['id' => $reclamationId]);
        }
        return $this->redirectToRoute('app_back_reclamation_index');
    }

    #[Route('/export/excel', name: 'app_back_reclamation_export_excel', methods: ['GET'])]
    public function exportExcel(ReclamationRepository $repository): Response
    {
        $reclamations = $repository->findAll();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $headers = ['ID', 'Titre', 'Patient', 'Email', 'Catégorie', 'Priorité', 'Statut', 'Date'];
        foreach ($headers as $k => $v) {
            $sheet->setCellValue(chr(65 + $k) . '1', $v);
        }

        // Données
        $row = 2;
        foreach ($reclamations as $rec) {
            $sheet->setCellValue('A' . $row, $rec->getId());
            $sheet->setCellValue('B' . $row, $rec->getTitre());
            $sheet->setCellValue('C' . $row, $rec->getNomPatient());
            $sheet->setCellValue('D' . $row, $rec->getEmail());
            $sheet->setCellValue('E' . $row, $rec->getCategorie());
            $sheet->setCellValue('F' . $row, $rec->getPriorite());
            $sheet->setCellValue('G' . $row, $rec->getStatut());
            $sheet->setCellValue('H' . $row, $rec->getDateCreation()->format('d/m/Y H:i'));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="reclamations.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/export/pdf', name: 'app_back_reclamation_export_pdf', methods: ['GET'])]
    public function exportPdf(ReclamationRepository $repository): Response
    {
        $reclamations = $repository->findAll();
        
        // Configure Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('back/reclamation/pdf.html.twig', [
            'reclamations' => $reclamations
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reclamations.pdf"',
        ]);
    }
}
