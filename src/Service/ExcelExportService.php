<?php

namespace App\Service;

use App\Entity\Reclamation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    public function generateReclamationsExcel(array $reclamations): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Réclamations');

        // Headers
        $headers = ['ID', 'Patient', 'Email', 'Titre', 'Catégorie', 'Statut', 'Priorité', 'Date', 'Réponses'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Style headers
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:I1')->getFill()->getStartColor()->setARGB('FF0D6EFD');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Data rows
        $row = 2;
        foreach ($reclamations as $reclamation) {
            $sheet->setCellValue('A' . $row, $reclamation->getId());
            $sheet->setCellValue('B' . $row, $reclamation->getNomPatient());
            $sheet->setCellValue('C' . $row, $reclamation->getEmail());
            $sheet->setCellValue('D' . $row, $reclamation->getTitre());
            $sheet->setCellValue('E' . $row, $reclamation->getCategorie() ?? '-');
            $sheet->setCellValue('F' . $row, $reclamation->getStatut());
            $sheet->setCellValue('G' . $row, $reclamation->getPriorite());
            $sheet->setCellValue('H' . $row, $reclamation->getDateCreation()->format('d/m/Y H:i'));
            $sheet->setCellValue('I' . $row, count($reclamation->getReponses()));

            // Alternate row colors
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->getStartColor()->setARGB('FFF8F9FA');
            }

            $row++;
        }

        // Auto width columns
        foreach ($sheet->getColumnIterator('A', 'I') as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Generate response
        return $this->generateExcelResponse($spreadsheet, 'reclamations_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    public function generateReclamationDetailExcel(Reclamation $reclamation): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Réclamation');

        // Title
        $sheet->setCellValue('A1', 'DÉTAIL RÉCLAMATION #' . $reclamation->getId());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('FF0D6EFD');
        $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->mergeCells('A1:B1');

        $row = 3;

        // Complaint details
        $details = [
            'Titre' => $reclamation->getTitre(),
            'Patient' => $reclamation->getNomPatient(),
            'Email' => $reclamation->getEmail(),
            'Catégorie' => $reclamation->getCategorie() ?? '-',
            'Statut' => $reclamation->getStatut(),
            'Priorité' => $reclamation->getPriorite(),
            'Date de création' => $reclamation->getDateCreation()->format('d/m/Y H:i'),
        ];

        foreach ($details as $label => $value) {
            $sheet->setCellValue('A' . $row, $label . ':');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        $row++;
        $sheet->setCellValue('A' . $row, 'DESCRIPTION');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;
        $sheet->setCellValue('A' . $row, $reclamation->getDescription());
        $sheet->getStyle('A' . $row . ':B' . $row)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(100);
        $row += 3;

        // Responses section
        if (count($reclamation->getReponses()) > 0) {
            $sheet->setCellValue('A' . $row, 'RÉPONSES');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;

            // Response table headers
            $sheet->setCellValue('A' . $row, 'Admin');
            $sheet->setCellValue('B' . $row, 'Date');
            $sheet->setCellValue('C' . $row, 'Contenu');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->getStartColor()->setARGB('FF28A745');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
            $row++;

            foreach ($reclamation->getReponses() as $reponse) {
                $sheet->setCellValue('A' . $row, $reponse->getAdminNom());
                $sheet->setCellValue('B' . $row, $reponse->getDateReponse()->format('d/m/Y H:i'));
                $sheet->setCellValue('C' . $row, $reponse->getContenu());
                $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($row)->setRowHeight(60);

                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->getStartColor()->setARGB('FFF8F9FA');
                }
                $row++;
            }
        }

        // Auto width columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(false);
        $sheet->getColumnDimension('C')->setWidth(50);

        return $this->generateExcelResponse($spreadsheet, 'reclamation_' . $reclamation->getId() . '_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    private function generateExcelResponse(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
        ]);
    }
}
