<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwoFactorGateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        if ($route === '') {
            return;
        }

        $allowedRoutes = [
            'app_2fa',
            'app_login',
            'app_logout',
            'api_face_login',
        ];

        if (in_array($route, $allowedRoutes, true) || str_starts_with($route, '_')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $session = $request->getSession();
        if (!$session) {
            return;
        }

        $passed = $session->get('2fa_passed') === true;
        $sessionUserId = (int) $session->get('2fa_user_id');

        if (!$passed || $sessionUserId !== (int) $user->getId()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_2fa')));
        }
    }
}
