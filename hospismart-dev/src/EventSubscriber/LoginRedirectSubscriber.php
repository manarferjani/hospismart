<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        if (!$user instanceof User) {
            return;
        }

        $targetUrl = $this->getRedirectUrl($user);
        $event->setResponse(new RedirectResponse($targetUrl));
    }

    private function getRedirectUrl(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->urlGenerator->generate('app_dashboard');
        }
        if (in_array('ROLE_MEDECIN', $roles, true)) {
            return $this->urlGenerator->generate('app_dashboard');
        }
        if (in_array('ROLE_PATIENT', $roles, true)) {
            return $this->urlGenerator->generate('app_patient_coordonnees');
        }
        return $this->urlGenerator->generate('app_patient_coordonnees');
    }
}
