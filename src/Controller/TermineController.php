<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\NavigationService;
use App\Service\TerminProviderService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TermineController extends AbstractBaseController
{
    public function __construct(
        private readonly NavigationService $navigation,
        private readonly TerminProviderService $terminProvider,
    ) {
    }

    #[Route(
        path: '/termine',
        name: 'app_termine',
        methods: ['GET']
    )]
    public function termine(): Response
    {
        return $this->render(
            'pages/termine.html.twig',
            [
                'slug'     => 'termine',
                'navItems' => $this->navigation->getItems(),
                'pageMeta' => $this->loadPageMetadata('termine'),
                'termine'  => $this->terminProvider->getUpcoming(),
            ]
        );
    }
}
