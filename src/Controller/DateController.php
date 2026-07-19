<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\DateProviderService;
use App\Service\NavigationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DateController extends AbstractBaseController
{
    public function __construct(
        private readonly NavigationService $navigation,
        private readonly DateProviderService $dateProvider,
    ) {
    }

    #[Route(
        path: '/termine',
        name: 'app_dates',
        methods: ['GET']
    )]
    public function dates(): Response
    {
        return $this->render(
            'pages/dates.html.twig',
            [
                'slug'        => 'termine',
                'navItems'    => $this->navigation->getItems(),
                'footerItems' => $this->navigation->getFooterItems(),
                'pageMeta'    => $this->loadPageMetadata('termine'),
                'dates'       => $this->dateProvider->getUpcoming(),
            ]
        );
    }
}
