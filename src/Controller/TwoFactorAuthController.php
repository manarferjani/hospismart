<?php

namespace App\Controller;

use App\Entity\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Doctrine\ORM\EntityManagerInterface;
use PragmaRX\Google2FA\Google2FA;
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
        $google2fa = new Google2FA();
        $session = $request->getSession();

        // Si l'utilisateur a déjà passé le 2FA avec succès pour cette session
        if ($session->get('2fa_passed') === true) {
            return $this->redirectToRoute('app_home');
        }

        $secret = $user->getGoogleAuthenticatorSecret();
        $isSetup = false;
        $qrCodeInline = null;

        // Si l'utilisateur n'a pas encore configuré de 2FA
        if (!$secret) {
            $isSetup = true;
            // Générer un nouveau secret
            $secret = $google2fa->generateSecretKey();
            $user->setGoogleAuthenticatorSecret($secret);
            $entityManager->persist($user);
            $entityManager->flush();

            // Générer l'URL pour Google Authenticator
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'Hospismart',
                $user->getEmail(),
                $secret
            );

            // Générer l'image SVG QR Code avec BaconQrCode
            $renderer = new ImageRenderer(
                new RendererStyle(300),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $qrCodeSvg = $writer->writeString($qrCodeUrl);
            $qrCodeInline = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
        }

        // Vérification de la soumission du formulaire
        if ($request->isMethod('POST')) {
            $code = $request->request->get('auth_code');

            if ($google2fa->verifyKey($secret, $code)) {
                $session->set('2fa_passed', true);

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
}
