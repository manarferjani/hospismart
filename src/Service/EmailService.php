<?php

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    public function sendStockAlert(string $to, string $medicamentName, int $quantity, int $threshold): void
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Désactiver le debug (mettre SMTP::DEBUG_SERVER pour déboguer)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'arfaouimahmoud62@gmail.com';
            $mail->Password   = 'perndfwrzmgjfnus';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Bypass SSL certificate issues
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('arfaouimahmoud62@gmail.com', 'HospiSmart - Gestion Hospitalière');
            $mail->addAddress($to);

            // Determine alert level
            $alertLevel = 'warning';
            $alertColor = '#e67e22';
            $alertIcon = '⚠️';
            $alertTitle = 'Stock Faible';
            $alertBg = '#fef9e7';
            $alertBorder = '#f39c12';

            if ($quantity === 0) {
                $alertLevel = 'critical';
                $alertColor = '#c0392b';
                $alertIcon = '🚨';
                $alertTitle = 'RUPTURE DE STOCK';
                $alertBg = '#fdedec';
                $alertBorder = '#e74c3c';
            } elseif ($quantity <= ($threshold / 2)) {
                $alertLevel = 'danger';
                $alertColor = '#e74c3c';
                $alertIcon = '🔴';
                $alertTitle = 'Stock Critique';
                $alertBg = '#fdedec';
                $alertBorder = '#e74c3c';
            }

            $percentage = $threshold > 0 ? round(($quantity / $threshold) * 100) : 0;
            $date = date('d/m/Y à H:i');
            $refNumber = 'ALT-' . date('Ymd-His');

            // Content
            $mail->isHTML(true);
            $mail->Subject = "$alertIcon Alerte Stock : $medicamentName — $alertTitle";
            $mail->Body = $this->buildEmailBody(
                $medicamentName, $quantity, $threshold,
                $alertColor, $alertTitle, $alertIcon, $alertBg, $alertBorder,
                $percentage, $date, $refNumber
            );

            // Plain text version for email clients that don't support HTML
            $mail->AltBody = "ALERTE STOCK - $alertTitle\n\n"
                . "Médicament : $medicamentName\n"
                . "Quantité actuelle : $quantity\n"
                . "Seuil d'alerte : $threshold\n"
                . "Date : $date\n"
                . "Référence : $refNumber\n\n"
                . "Veuillez prendre les mesures nécessaires pour réapprovisionner ce médicament.\n"
                . "— HospiSmart";

            $mail->send();
        } catch (Exception $e) {
            // Log l'erreur silencieusement en production
            error_log("HospiSmart EmailService Error: {$mail->ErrorInfo}");
        }
    }

    private function buildEmailBody(
        string $medicamentName, int $quantity, int $threshold,
        string $alertColor, string $alertTitle, string $alertIcon,
        string $alertBg, string $alertBorder,
        int $percentage, string $date, string $refNumber
    ): string {
        $progressBarColor = $alertColor;
        $progressWidth = min($percentage, 100);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerte Stock - HospiSmart</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; color: #2c3e50;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f2f5; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%); padding: 30px 40px; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <div style="font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: 1px;">
                                            🏥 HospiSmart
                                        </div>
                                        <div style="font-size: 13px; color: #aed6f1; margin-top: 6px; letter-spacing: 0.5px;">
                                            Système de Gestion Hospitalière — Module Pharmacie
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Alert Banner -->
                    <tr>
                        <td style="background-color: {$alertColor}; padding: 18px 40px; text-align: center;">
                            <span style="font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">
                                {$alertIcon} {$alertTitle} {$alertIcon}
                            </span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 35px 40px;">
                            
                            <!-- Greeting -->
                            <p style="font-size: 15px; color: #555; margin: 0 0 20px 0; line-height: 1.6;">
                                Bonjour,<br>
                                Le système de gestion des stocks <strong>HospiSmart</strong> a détecté un niveau de stock anormalement bas nécessitant votre attention immédiate.
                            </p>

                            <!-- Medication Info Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: {$alertBg}; border-left: 5px solid {$alertBorder}; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 25px 30px;">
                                        <div style="font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;">
                                            Médicament concerné
                                        </div>
                                        <div style="font-size: 22px; font-weight: 700; color: #2c3e50; margin-bottom: 4px;">
                                            💊 {$medicamentName}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Stock Details -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 25px 0;">
                                <tr>
                                    <!-- Current Quantity -->
                                    <td width="48%" style="background-color: #fafafa; border-radius: 10px; padding: 20px; text-align: center; border: 1px solid #eee;">
                                        <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                            Quantité Actuelle
                                        </div>
                                        <div style="font-size: 36px; font-weight: 800; color: {$alertColor};">
                                            {$quantity}
                                        </div>
                                        <div style="font-size: 12px; color: #aaa;">unités</div>
                                    </td>
                                    <td width="4%"></td>
                                    <!-- Threshold -->
                                    <td width="48%" style="background-color: #fafafa; border-radius: 10px; padding: 20px; text-align: center; border: 1px solid #eee;">
                                        <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                            Seuil d'Alerte
                                        </div>
                                        <div style="font-size: 36px; font-weight: 800; color: #2c3e50;">
                                            {$threshold}
                                        </div>
                                        <div style="font-size: 12px; color: #aaa;">unités</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Progress Bar -->
                            <div style="margin: 20px 0;">
                                <div style="font-size: 12px; color: #888; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
                                    Niveau de stock : {$percentage}%
                                </div>
                                <div style="background-color: #ecf0f1; border-radius: 10px; height: 14px; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, {$progressBarColor}, {$progressBarColor}aa); width: {$progressWidth}%; height: 100%; border-radius: 10px; transition: width 0.5s;"></div>
                                </div>
                            </div>

                            <!-- Separator -->
                            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

                            <!-- Actions -->
                            <div style="margin: 25px 0;">
                                <div style="font-size: 15px; font-weight: 600; color: #2c3e50; margin-bottom: 12px;">
                                    📋 Actions recommandées :
                                </div>
                                <table role="presentation" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #555;">
                                    <tr>
                                        <td style="padding: 6px 0; vertical-align: top;">✅</td>
                                        <td style="padding: 6px 0 6px 10px;">Vérifier le stock physique du médicament <strong>{$medicamentName}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; vertical-align: top;">✅</td>
                                        <td style="padding: 6px 0 6px 10px;">Passer une commande de réapprovisionnement auprès du fournisseur</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; vertical-align: top;">✅</td>
                                        <td style="padding: 6px 0 6px 10px;">Informer le personnel médical de la situation</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; vertical-align: top;">✅</td>
                                        <td style="padding: 6px 0 6px 10px;">Mettre à jour le stock dans le système après réception</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Reference Info -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-top: 25px;">
                                <tr>
                                    <td style="padding: 18px 22px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 12px; color: #888;">
                                            <tr>
                                                <td>📅 <strong>Date :</strong> {$date}</td>
                                                <td style="text-align: right;">🔖 <strong>Réf :</strong> {$refNumber}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1a252f; padding: 28px 40px; text-align: center;">
                            <div style="font-size: 14px; font-weight: 600; color: #ecf0f1; margin-bottom: 6px;">
                                🏥 HospiSmart — Gestion Hospitalière Intelligente
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d; line-height: 1.6;">
                                Cet email a été envoyé automatiquement par le module de gestion de stock.<br>
                                Merci de ne pas répondre à ce message.
                            </div>
                            <div style="margin-top: 12px; font-size: 10px; color: #566573;">
                                © 2026 HospiSmart. Tous droits réservés.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
