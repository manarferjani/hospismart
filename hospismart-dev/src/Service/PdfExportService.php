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
        $total = count($reclamations);
        $statCounts = ['En attente' => 0, 'En cours' => 0, 'Traité' => 0];
        $prioCounts = ['Urgente' => 0, 'Haute' => 0, 'Normale' => 0, 'Basse' => 0];
        
        foreach ($reclamations as $r) {
            $statCounts[$r->getStatut()]++;
            $prioCounts[$r->getPriorite()]++;
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 30px; }
                body { font-family: "Helvetica", Arial, sans-serif; color: #334155; margin: 0; padding: 0; background-color: #ffffff; font-size: 11px; }
                
                /* Robust Layout with Tables */
                .full-width { width: 100%; border-collapse: collapse; }
                
                /* Header Design */
                .header-table { margin-bottom: 40px; border-bottom: 2px solid #3b82f6; padding-bottom: 20px; }
                .brand-name { font-size: 24px; font-weight: 800; color: #1e40af; }
                .brand-name span { color: #3b82f6; }
                .report-meta { text-align: right; color: #64748b; }
                .report-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; text-transform: uppercase; }

                /* Stats Dashboard - Row of Boxes */
                .stats-table { margin-bottom: 30px; }
                .stat-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; text-align: center; width: 23%; }
                .stat-number { font-size: 20px; font-weight: 800; color: #3b82f6; display: block; }
                .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: 600; }

                /* Analysis Section */
                .section-header { background: #f1f5f9; padding: 8px 15px; border-radius: 6px; margin-bottom: 15px; font-weight: 700; color: #334155; }
                
                /* Chart Layout */
                .analysis-table { margin-bottom: 30px; }
                .bar-container { background: #f1f5f9; height: 12px; border-radius: 6px; width: 100%; position: relative; overflow: hidden; }
                .bar-fill { height: 100%; background: #3b82f6; border-radius: 6px; }
                .bar-fill-traite { background: #10b981; }
                .bar-fill-attente { background: #f59e0b; }

                /* Data Table */
                .data-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
                .data-table th { background: #1e293b; color: white; padding: 10px; text-align: left; font-size: 9px; text-transform: uppercase; }
                .data-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }
                .data-table tr:nth-child(even) { background: #fbfcfd; }
                
                .badge { padding: 3px 8px; border-radius: 4px; font-size: 8px; font-weight: 700; text-transform: uppercase; display: inline-block; }
                .b-attente { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
                .b-cours { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
                .b-traite { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
                
                .prio-high { color: #ef4444; font-weight: 800; }
                .prio-norm { color: #3b82f6; }
                .prio-low { color: #10b981; }

                footer { position: fixed; bottom: 0; left: 0; right: 0; height: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; text-align: center; color: #94a3b8; font-size: 9px; }
                .page-number:after { content: counter(page); }
            </style>
        </head>
        <body>
            <table class="full-width header-table">
                <tr>
                    <td>
                        <div class="brand-name">HOSPI<span>SMART</span></div>
                        <div style="color: #64748b; font-size: 10px;">Système de Management de la Qualité</div>
                    </td>
                    <td class="report-meta">
                        <h2 class="report-title">Rapport d\'Activités</h2>
                        <div>Généré le : ' . date('d/m/Y à H:i') . '</div>
                        <div>Période : Mensuelle</div>
                    </td>
                </tr>
            </table>

            <div class="section-header">Indicateurs de Performance</div>
            <table class="full-width stats-table">
                <tr>
                    <td class="stat-box">
                        <span class="stat-number">' . $total . '</span>
                        <span class="stat-label">Réclamations</span>
                    </td>
                    <td style="width: 2%;"></td>
                    <td class="stat-box">
                        <span class="stat-number" style="color: #10b981;">' . $statCounts['Traité'] . '</span>
                        <span class="stat-label">Résolues</span>
                    </td>
                    <td style="width: 2%;"></td>
                    <td class="stat-box">
                        <span class="stat-number" style="color: #f59e0b;">' . $statCounts['En cours'] . '</span>
                        <span class="stat-label">En traitement</span>
                    </td>
                    <td style="width: 2%;"></td>
                    <td class="stat-box">
                        <span class="stat-number" style="color: #ef4444;">' . $prioCounts['Urgente'] . '</span>
                        <span class="stat-label">Urgences</span>
                    </td>
                </tr>
            </table>

            <table class="full-width analysis-table">
                <tr>
                    <td style="width: 48%; vertical-align: top;">
                        <div style="font-weight: 700; margin-bottom: 10px;">Répartition par Statut</div>';
                        foreach ($statCounts as $lbl => $count) {
                            $pct = $total > 0 ? (int)(($count / $total) * 100) : 0;
                            $cls = $lbl === 'Traité' ? 'bar-fill-traite' : ($lbl === 'En attente' ? 'bar-fill-attente' : '');
                            $html .= '
                            <div style="margin-bottom: 8px;">
                                <table class="full-width">
                                    <tr>
                                        <td style="font-size: 9px; color: #64748b;">' . $lbl . '</td>
                                        <td style="text-align: right; font-weight: 700;">' . $pct . '%</td>
                                    </tr>
                                </table>
                                <div class="bar-container"><div class="bar-fill ' . $cls . '" style="width: ' . $pct . '%;"></div></div>
                            </div>';
                        }
        $html .= '
                    </td>
                    <td style="width: 4%;"></td>
                    <td style="width: 48%; vertical-align: top;">
                        <div style="font-weight: 700; margin-bottom: 10px;">Priorités Critiques</div>
                        <div style="background: #fff5f5; border: 1px solid #feb2b2; padding: 10px; border-radius: 6px;">
                            <div style="color: #c53030; font-size: 22px; font-weight: 800;">' . ($prioCounts['Urgente'] + $prioCounts['Haute']) . '</div>
                            <div style="color: #9b2c2c; font-size: 9px; text-transform: uppercase; font-weight: 700;">Dossiers Prioritaires</div>
                            <p style="margin: 5px 0 0; font-size: 9px; color: #c53030; line-height: 1.3;">Nécessitent une intervention immédiate sous 24h selon la charte qualité.</p>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="section-header">Registre des Entrées</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">REF</th>
                        <th>Sujet / Motif</th>
                        <th>Demandeur</th>
                        <th style="width: 80px;">État</th>
                        <th style="width: 70px;">Priorité</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($reclamations as $r) {
            $bCls = 'b-attente';
            if ($r->getStatut() === 'En cours') $bCls = 'b-cours';
            if ($r->getStatut() === 'Traité') $bCls = 'b-traite';
            
            $pCls = 'prio-norm';
            if (in_array($r->getPriorite(), ['Haute', 'Urgente'])) $pCls = 'prio-high';
            elseif ($r->getPriorite() === 'Basse') $pCls = 'prio-low';

            $html .= '
                <tr>
                    <td style="font-weight: 700;">#' . $r->getId() . '</td>
                    <td>
                        <div style="font-weight: 700; color: #1e293b;">' . htmlspecialchars($r->getTitre()) . '</div>
                        <div style="font-size: 9px; color: #94a3b8;">' . $r->getDateCreation()->format('d/m/Y') . '</div>
                    </td>
                    <td>' . htmlspecialchars($r->getNomPatient()) . '</td>
                    <td><span class="badge ' . $bCls . '">' . $r->getStatut() . '</span></td>
                    <td><span class="' . $pCls . '">' . $r->getPriorite() . '</span></td>
                </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <footer>
                HospiSmart Document Officiel - Confidentiel - Page <span class="page-number"></span>
            </footer>
        </body>
        </html>';

        return $html;
    }

    private function renderReclamationDetailHtml(Reclamation $reclamation): string
    {
        $status = $reclamation->getStatut();
        $color = '#3b82f6';
        if ($status === 'Traité') $color = '#10b981';
        if ($status === 'En attente') $color = '#f59e0b';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 40px; }
                body { font-family: "Helvetica", Arial, sans-serif; color: #334155; margin: 0; padding: 0; line-height: 1.5; font-size: 11px; }
                .full-width { width: 100%; border-collapse: collapse; }
                
                .header-strip { height: 5px; background: ' . $color . '; margin-bottom: 30px; }
                
                .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px; }
                .box-title { font-weight: 800; color: #1e293b; text-transform: uppercase; font-size: 10px; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 5px; }
                
                .label { color: #64748b; font-size: 9px; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
                .value { color: #1e293b; font-weight: 600; font-size: 12px; margin-bottom: 15px; }
                
                .status-block { background: ' . $color . '; color: white; padding: 10px 20px; border-radius: 6px; display: inline-block; font-weight: 800; margin-bottom: 30px; }
                
                .desc-box { background: #f8fafc; padding: 15px; border-left: 4px solid #3b82f6; border-radius: 4px; font-style: italic; }
                
                .timeline-item { border-left: 2px solid #e2e8f0; padding-left: 20px; position: relative; margin-bottom: 20px; }
                .timeline-item:before { content: ""; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #10b981; }
                .res-header { font-size: 10px; font-weight: 700; color: #10b981; margin-bottom: 5px; }
                .res-content { background: #f0fdf4; padding: 12px; border-radius: 6px; color: #166534; }
                
                .footer { text-align: center; margin-top: 50px; color: #94a3b8; font-size: 9px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class="header-strip"></div>
            
            <table class="full-width">
                <tr>
                    <td style="vertical-align: top;">
                        <div style="font-size: 20px; font-weight: 800; color: #1e40af;">HOSPI<span>SMART</span></div>
                        <div style="font-size: 14px; font-weight: 700; margin-top: 5px;">DOSSIER PATIENT #'. $reclamation->getId() .'</div>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <div class="status-block">'. strtoupper($status) .'</div>
                        <div style="color: #64748b;">Réception : '. $reclamation->getDateCreation()->format('d/m/Y H:i') .'</div>
                    </td>
                </tr>
            </table>

            <div class="box" style="margin-top: 30px;">
                <div class="box-title">Identification & Contexte</div>
                <table class="full-width">
                    <tr>
                        <td style="width: 33%;">
                            <div class="label">Patient</div>
                            <div class="value">'. htmlspecialchars($reclamation->getNomPatient()) .'</div>
                        </td>
                        <td style="width: 33%;">
                            <div class="label">Coordonnées</div>
                            <div class="value">'. htmlspecialchars($reclamation->getEmail()) .'</div>
                        </td>
                        <td style="width: 33%;">
                            <div class="label">Catégorie</div>
                            <div class="value">'. ($reclamation->getCategorie() ?? "Médical") .'</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <div class="label">Niveau de Priorité</div>
                            <div class="value" style="color: '. ($reclamation->getPriorite() == "Urgente" ? "#ef4444" : "#1e40af") .'">'. $reclamation->getPriorite() .'</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="box">
                <div class="box-title">Objet du Signalement</div>
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">'. htmlspecialchars($reclamation->getTitre()) .'</div>
                <div class="desc-box">
                    '. nl2br(htmlspecialchars($reclamation->getDescription())) .'
                </div>
            </div>';

        if ($reclamation->getReponses()->count() > 0) {
            $html .= '
            <div class="box">
                <div class="box-title">Suivi & Actions Correctives</div>';
                foreach ($reclamation->getReponses() as $rep) {
                    $html .= '
                    <div class="timeline-item">
                        <div class="res-header">ACTION LE '. $rep->getDateReponse()->format('d/m/Y à H:i') .' PAR '. strtoupper(htmlspecialchars($rep->getAdminNom())) .'</div>
                        <div class="res-content">'. nl2br(htmlspecialchars($rep->getContenu())) .'</div>
                    </div>';
                }
            $html .= '</div>';
        }

        $html .= '
            <div class="footer">
                Ce document est généré informatiquement par la plateforme HospiSmart.<br>
                ID Audit : HS-'. time() .'-'. $reclamation->getId() .'
            </div>
        </body>
        </html>';

        return $html;
    }
}
