<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class TwoFactorAuthController extends AbstractController
{
    #[Route('/2fa', name: 'app_2fa', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $session = $request->getSession();

        // Si l'utilisateur a déjà passé le 2FA avec succès pour cette session
        if ($session->get('2fa_passed') === true && (int) $session->get('2fa_user_id') === (int) $user->getId()) {
            return $this->redirectToRoute('app_home');
        }

        $secret = $user->getGoogleAuthenticatorSecret();
        $isSetup = false;
        $qrCodeInline = null;

        // Si l'utilisateur n'a pas encore configuré de 2FA
        if (!$secret) {
            $isSetup = true;
            // Générer un nouveau secret
            $secret = $this->generateBase32Secret();
            $user->setGoogleAuthenticatorSecret($secret);
            $entityManager->persist($user);
            $entityManager->flush();

            // Générer l'URL pour Google Authenticator
            $qrCodeUrl = $this->buildOtpAuthUrl('Hospismart', $user->getEmail(), $secret);

            // Générer une URL QR code sans dépendance PHP externe
            $qrCodeInline = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($qrCodeUrl);
        }

        // Vérification de la soumission du formulaire
        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('auth_code', ''));

            if ($code === '') {
                $this->addFlash('error', 'Veuillez entrer le code de vérification à 6 chiffres.');

                return $this->render('security/2fa.html.twig', [
                    'is_setup' => $isSetup,
                    'qr_code' => $qrCodeInline,
                    'secret' => $secret
                ]);
            }

            $storedStaticCode = trim((string) ($user->getTwoFactorCode() ?? ''));
            $isStaticCodeValid = $storedStaticCode !== '' && hash_equals($storedStaticCode, $code);

            if ($isStaticCodeValid || $this->verifyTotpCode($secret, (string) $code)) {
                $session->set('2fa_passed', true);
                $session->set('2fa_user_id', $user->getId());

                $this->addFlash('success', 'Authentification à deux facteurs réussie.');

                // Rediriger vers la page d'accueil ou dashboard
                if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MEDECIN', $user->getRoles())) {
                    return $this->redirectToRoute('app_dashboard');
                }
                return $this->redirectToRoute('app_patient_coordonnees');
            } else {
                $this->addFlash('error', 'Code de vérification incorrect.');
            }
        }

        return $this->render('security/2fa.html.twig', [
            'is_setup' => $isSetup,
            'qr_code' => $qrCodeInline,
            'secret' => $secret
        ]);
    }

    private function generateBase32Secret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $secret;
    }

    private function buildOtpAuthUrl(string $issuer, string $accountName, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $issuerEncoded = rawurlencode($issuer);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            $secret,
            $issuerEncoded
        );
    }

    private function verifyTotpCode(string $base32Secret, string $inputCode, int $window = 1): bool
    {
        $inputCode = preg_replace('/\s+/', '', $inputCode);
        if (!preg_match('/^\d{6}$/', (string) $inputCode)) {
            return false;
        }

        $secretKey = $this->base32Decode($base32Secret);
        if ($secretKey === '') {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $calculated = $this->calculateTotp($secretKey, $timeSlice + $i);
            if (hash_equals($calculated, (string) $inputCode)) {
                return true;
            }
        }

        return false;
    }

    private function calculateTotp(string $secretKey, int $timeSlice): string
    {
        $binaryTime = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binaryTime, $secretKey, true);
        $offset = ord($hash[19]) & 0xf;

        $truncatedHash = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        );

        $otp = $truncatedHash % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));

        if ($base32 === '') {
            return '';
        }

        $bits = '';
        $length = strlen($base32);

        for ($i = 0; $i < $length; $i++) {
            $val = strpos($alphabet, $base32[$i]);
            if ($val === false) {
                return '';
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $bytes .= chr(bindec(substr($bits, $i, 8)));
        }

        return $bytes;
    }
}
