<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\DateProviderService;
use App\Service\FormRegistrationService;
use App\Service\NavigationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractBaseController
{
    private const array MESSAGES = [
        'success' => 'Vielen Dank für deine Anmeldung! Wir melden uns zeitnah bei dir.',
        'invalid' => 'Bitte korrigiere die markierten Felder und sende das Formular erneut ab.',
        'rate'    => 'Bitte warte einen Moment, bevor du das Formular erneut absendest.',
        'mail'    => 'Leider konnte die E-Mail nicht versendet werden. Bitte versuche es in Kürze erneut.',
    ];

    public function __construct(
        private readonly NavigationService $navigation,
        private readonly FormRegistrationService $formRegistrationService,
        private readonly DateProviderService $dateProvider,
    ) {
    }

    #[Route(
        path: '/anmeldung',
        name: 'app_registration',
        methods: ['GET', 'POST']
    )]
    public function registration(Request $request): Response
    {
        $result = $this->formRegistrationService->handle();
        $form = $this->formRegistrationService->getForm();

        if (null !== $result && $request->isXmlHttpRequest()) {
            return $this->submissionJson($result, $form, self::MESSAGES);
        }

        return $this->render(
            'pages/registration.html.twig',
            [
                'slug'        => 'anmeldung',
                'navItems'    => $this->navigation->getItems(),
                'footerItems' => $this->navigation->getFooterItems(),
                'pageMeta'    => $this->loadPageMetadata('anmeldung'),
                'nextDate'    => $this->dateProvider->getNext(),
                'form'        => $form->createView(),
                'result'      => $result,
                'messages'    => self::MESSAGES,
            ]
        );
    }
}
