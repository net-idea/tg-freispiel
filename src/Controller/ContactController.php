<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\FormContactService;
use App\Service\NavigationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractBaseController
{
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
    public function contact(): Response
    {
        // Handle form submission first (this will process POST and return redirect on success)
        if ($response = $this->formContactService->handle()) {
            return $response;
        }

        // Get form for rendering (either initial GET or POST with validation errors)
        $form = $this->formContactService->getForm();

        return $this->render(
            'pages/kontakt.html.twig',
            [
                'slug'     => 'kontakt',
                'navItems' => $this->navigation->getItems(),
                'form'     => $form->createView(),
            ]
        );
    }
}
