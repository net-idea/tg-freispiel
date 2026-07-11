<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationResult;
use App\Dto\RegistrationStatus;
use App\Service\FormRegistrationService;
use App\Service\NavigationService;
use App\Service\TerminProviderService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnmeldungController extends AbstractBaseController
{
    private const string MESSAGE_SUCCESS = 'Vielen Dank für deine Anmeldung! Wir melden uns zeitnah bei dir.';
    private const string MESSAGE_INVALID = 'Bitte korrigiere die markierten Felder und sende das Formular erneut ab.';
    private const string MESSAGE_RATE = 'Bitte warte einen Moment, bevor du das Formular erneut absendest.';
    private const string MESSAGE_MAIL = 'Leider konnte die E-Mail nicht versendet werden. Bitte versuche es in Kürze erneut.';

    public function __construct(
        private readonly NavigationService $navigation,
        private readonly FormRegistrationService $formRegistrationService,
        private readonly TerminProviderService $terminProvider,
    ) {
    }

    #[Route(
        path: '/anmeldung',
        name: 'app_anmeldung',
        methods: ['GET', 'POST']
    )]
    public function anmeldung(Request $request): Response
    {
        $result = $this->formRegistrationService->handle();
        $form = $this->formRegistrationService->getForm();

        if (null !== $result && $request->isXmlHttpRequest()) {
            return $this->json(...$this->buildJsonResponse($result, $form));
        }

        return $this->render(
            'pages/anmeldung.html.twig',
            [
                'slug'       => 'anmeldung',
                'navItems'   => $this->navigation->getItems(),
                'pageMeta'   => $this->loadPageMetadata('anmeldung'),
                'nextTermin' => $this->terminProvider->getNext(),
                'form'       => $form->createView(),
                'result'     => $result,
                'messages'   => [
                    'success' => self::MESSAGE_SUCCESS,
                    'invalid' => self::MESSAGE_INVALID,
                    'rate'    => self::MESSAGE_RATE,
                    'mail'    => self::MESSAGE_MAIL,
                ],
            ]
        );
    }

    /**
     * Map a RegistrationResult to JSON payload + HTTP status for the Ajax flow.
     *
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function buildJsonResponse(RegistrationResult $result, FormInterface $form): array
    {
        if ($result->shouldPresentAsSuccess()) {
            return [['success' => true, 'message' => self::MESSAGE_SUCCESS], Response::HTTP_OK];
        }

        return match ($result->status) {
            RegistrationStatus::INVALID => [
                [
                    'success' => false,
                    'message' => self::MESSAGE_INVALID,
                    'errors'  => $this->collectFormErrors($form),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            RegistrationStatus::RATE_LIMITED => [
                ['success' => false, 'message' => self::MESSAGE_RATE],
                Response::HTTP_TOO_MANY_REQUESTS,
            ],
            default => [
                ['success' => false, 'message' => self::MESSAGE_MAIL],
                Response::HTTP_SERVICE_UNAVAILABLE,
            ],
        };
    }

    /**
     * Collect validation messages keyed by child field name; form-level
     * errors (e.g. CSRF) are grouped under "_global".
     *
     * @return array<string, array<int, string>>
     */
    private function collectFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors['_global'][] = $error->getMessage();
        }

        foreach ($form as $child) {
            foreach ($child->getErrors() as $error) {
                $errors[$child->getName()][] = $error->getMessage();
            }
        }

        return $errors;
    }
}
