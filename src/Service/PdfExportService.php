<?php

namespace App\Service;

use App\Entity\Reclamation;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExportService
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('defaultMediaType', 'screen');

        $this->dompdf = new Dompdf($options);
        $this->dompdf->setPaper('A4', 'portrait');
    }

    public function generateReclamationsPdf(array $reclamations, string $filename = 'reclamations.pdf'): string
    {
        $html = $this->renderReclamationsHtml($reclamations);
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();

        return $this->dompdf->output();
    }

    public function generateReclamationDetailPdf(Reclamation $reclamation, string $filename = 'reclamation.pdf'): string
    {
        $html = $this->renderReclamationDetailHtml($reclamation);
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();

        return $this->dompdf->output();
    }

    private function renderReclamationsHtml(array $reclamations): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport des Réclamations</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0d6efd; padding-bottom: 20px; }
                .header h1 { color: #0d6efd; margin: 0; }
                .header p { margin: 5px 0; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th { background-color: #0d6efd; color: white; padding: 12px; text-align: left; font-weight: bold; }
                td { padding: 10px 12px; border-bottom: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .status-en-attente { background-color: #ffe69c; }
                .status-en-cours { background-color: #d1ecf1; }
                .status-traite { background-color: #d1f0d5; }
                .priority-basse { color: #28a745; font-weight: bold; }
                .priority-normale { color: #0d6efd; font-weight: bold; }
                .priority-haute { color: #fd7e14; font-weight: bold; }
                .priority-urgente { color: #dc3545; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Rapport des Réclamations</h1>
                <p>Date d\'édition: ' . date('d/m/Y H:i') . '</p>
                <p>Total: ' . count($reclamations) . ' réclamation(s)</p>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Patient</th>
                    <th>Statut</th>
                    <th>Priorité</th>
                    <th>Date</th>
                </tr>';

        foreach ($reclamations as $reclamation) {
            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $reclamation->getStatut()));
            $priorityClass = 'priority-' . strtolower($reclamation->getPriorite());
            
            $html .= '
                <tr>
                    <td>#' . $reclamation->getId() . '</td>
                    <td>' . htmlspecialchars($reclamation->getTitre()) . '</td>
                    <td>' . htmlspecialchars($reclamation->getNomPatient()) . '</td>
                    <td class="' . $statusClass . '">' . $reclamation->getStatut() . '</td>
                    <td class="' . $priorityClass . '">' . $reclamation->getPriorite() . '</td>
                    <td>' . $reclamation->getDateCreation()->format('d/m/Y H:i') . '</td>
                </tr>';
        }

        $html .= '
            </table>

            <div class="footer">
                <p>Document généré automatiquement par le système de gestion des réclamations</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    private function renderReclamationDetailHtml(Reclamation $reclamation): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Réclamation #' . $reclamation->getId() . '</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.8; }
                .header { background-color: #0d6efd; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
                .header h1 { margin: 0; }
                .section { margin-bottom: 20px; border-left: 4px solid #0d6efd; padding-left: 15px; }
                .section h3 { color: #0d6efd; margin-top: 0; }
                .info-grid { display: table; width: 100%; margin-bottom: 20px; }
                .info-row { display: table-row; }
                .info-label { display: table-cell; font-weight: bold; width: 150px; padding: 8px; background-color: #f9f9f9; }
                .info-value { display: table-cell; padding: 8px; }
                .response { background-color: #e8f5e9; padding: 15px; margin-bottom: 10px; border-left: 4px solid #28a745; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Réclamation #' . $reclamation->getId() . '</h1>
                <p style="margin: 0;">' . htmlspecialchars($reclamation->getTitre()) . '</p>
            </div>

            <div class="section">
                <h3>Informations Patient</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom:</div>
                        <div class="info-value">' . htmlspecialchars($reclamation->getNomPatient()) . '</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">' . htmlspecialchars($reclamation->getEmail()) . '</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Catégorie:</div>
                        <div class="info-value">' . ($reclamation->getCategorie() ?? 'Non spécifiée') . '</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Statut et Priorité</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Statut:</div>
                        <div class="info-value"><strong>' . $reclamation->getStatut() . '</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Priorité:</div>
                        <div class="info-value"><strong>' . $reclamation->getPriorite() . '</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date:</div>
                        <div class="info-value">' . $reclamation->getDateCreation()->format('d/m/Y à H:i') . '</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Description</h3>
                <p>' . nl2br(htmlspecialchars($reclamation->getDescription())) . '</p>
            </div>';

        if ($reclamation->getReponses()->count() > 0) {
            $html .= '
            <div class="section">
                <h3>Réponses (' . $reclamation->getReponses()->count() . ')</h3>';

            foreach ($reclamation->getReponses() as $reponse) {
                $html .= '
                <div class="response">
                    <strong>' . htmlspecialchars($reponse->getAdminNom()) . '</strong> - ' . $reponse->getDateReponse()->format('d/m/Y à H:i') . '<br><br>
                    ' . nl2br(htmlspecialchars($reponse->getContenu())) . '
                </div>';
            }

            $html .= '
            </div>';
        }

        $html .= '
            <div class="footer">
                <p>Document généré automatiquement le ' . date('d/m/Y à H:i') . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
