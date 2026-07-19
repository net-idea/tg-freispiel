<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\SubmissionResult;
use App\Dto\SubmissionStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBaseController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    public function loadPageMetadata(string $slug): array
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        // Load page metadata from content/_pages.php
        $pagesFile = $projectDir . '/content/_pages.php';
        $pages = is_file($pagesFile) ? (require $pagesFile) : [];
        $metaSlug = \in_array($slug, ['', 'main', 'index'], true) ? 'start' : $slug;
        $defaultPath = '/' . ('start' === $metaSlug ? '' : $metaSlug);

        $defaults = [
            'title'       => 'Theatergruppe Freispiel in Dormagen | Proben, Termine & Probestunde',
            'description' => 'Theatergruppe Freispiel aus Dormagen – wir bringen Geschichten auf die Bühne. Komm vorbei, mach mit und erlebe mit uns die Magie des Theaters.',
            'destination' => 'Uni',
            'path'        => $defaultPath,
            'canonical'   => $defaultPath,
            'robots'      => 'index,follow,max-image-preview:large',
            'og_image'    => '/images/stage-background-mystical.webp',
        ];

        /** @var array<string,mixed> $pageMeta */
        $pageMeta = array_replace($defaults, $pages[$metaSlug] ?? []);

        return $pageMeta;
    }

    /**
     * Shared Ajax response for form submissions: maps a SubmissionResult to
     * JSON payload + HTTP status. $messages needs the keys success, invalid,
     * rate and mail.
     *
     * @param array{success: string, invalid: string, rate: string, mail: string} $messages
     */
    protected function submissionJson(SubmissionResult $result, FormInterface $form, array $messages): JsonResponse
    {
        if ($result->shouldPresentAsSuccess()) {
            return $this->json(['success' => true, 'message' => $messages['success']]);
        }

        return match ($result->status) {
            SubmissionStatus::INVALID => $this->json(
                [
                    'success' => false,
                    'message' => $messages['invalid'],
                    'errors'  => $this->collectFormErrors($form),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            ),
            SubmissionStatus::RATE_LIMITED => $this->json(
                ['success' => false, 'message' => $messages['rate']],
                Response::HTTP_TOO_MANY_REQUESTS
            ),
            default => $this->json(
                ['success' => false, 'message' => $messages['mail']],
                Response::HTTP_SERVICE_UNAVAILABLE
            ),
        };
    }

    /**
     * Collect validation messages keyed by child field name; form-level
     * errors (e.g. CSRF) are grouped under "_global".
     *
     * @return array<string, array<int, string>>
     */
    protected function collectFormErrors(FormInterface $form): array
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
