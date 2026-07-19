<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\FormContactService;
use App\Service\NavigationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractBaseController
{
    private const array MESSAGES = [
        'success' => 'Vielen Dank für deine Nachricht! Wir melden uns zeitnah bei dir.',
        'invalid' => 'Bitte korrigiere die markierten Felder und sende das Formular erneut ab.',
        'rate'    => 'Bitte warte einen Moment, bevor du das Formular erneut absendest.',
        'mail'    => 'Leider konnte die E-Mail nicht versendet werden. Bitte versuche es in Kürze erneut.',
    ];

    public function __construct(
        private readonly NavigationService $navigation,
        private readonly FormContactService $formContactService,
    ) {
    }

    #[Route(
        path: '/kontakt',
        name: 'app_contact',
        methods: ['GET', 'POST']
    )]
    public function contact(Request $request): Response
    {
        $result = $this->formContactService->handle();
        $form = $this->formContactService->getForm();

        if (null !== $result && $request->isXmlHttpRequest()) {
            return $this->submissionJson($result, $form, self::MESSAGES);
        }

        return $this->render(
            'pages/contact.html.twig',
            [
                'slug'        => 'kontakt',
                'navItems'    => $this->navigation->getItems(),
                'footerItems' => $this->navigation->getFooterItems(),
                'pageMeta'    => $this->loadPageMetadata('kontakt'),
                'form'        => $form->createView(),
                'result'      => $result,
                'messages'    => self::MESSAGES,
            ]
        );
    }
}
