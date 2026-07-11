<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\RegistrationResult;
use App\Entity\FormRegistrationEntity;
use App\Entity\FormSubmissionMetaEntity;
use App\Form\FormRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class FormRegistrationService extends AbstractFormService
{
    private const string SESSION_RATE_KEY = 'rf_times';

    private ?FormInterface $form = null;

    public function __construct(
        private readonly FormFactoryInterface $forms,
        private readonly RequestStack $requests,
        private readonly MailManService $mailMan,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getForm(): FormInterface
    {
        if (null === $this->form) {
            $this->form = $this->forms->create(
                FormRegistrationType::class,
                new FormRegistrationEntity()
            );
        }

        return $this->form;
    }

    /**
     * Handle a registration submission. Returns null when nothing was
     * submitted (plain GET), otherwise a RegistrationResult describing the
     * outcome. Presentation (JSON vs. HTML) is up to the controller.
     */
    public function handle(): ?RegistrationResult
    {
        $boot = $this->handleFormRequest($this->requests);

        if (null === $boot) {
            return null;
        }

        [$request, $form, $session] = $boot;

        $rl = $this->rateLimitCheck(
            $session,
            self::SESSION_RATE_KEY,
            self::RATE_MIN_INTERVAL_SECONDS,
            self::RATE_MAX_PER_WINDOW,
            self::RATE_WINDOW_SECONDS
        );

        if ($rl['blocked']) {
            return RegistrationResult::rateLimited();
        }

        // Honeypot: hidden website field (unmapped) or emailrep must be empty => if filled, pretend success
        $honey = trim($this->getHoneypotValue($form, 'website'));
        /** @var FormRegistrationEntity $registration */
        $registration = $form->getData();

        if ('' !== $honey || '' !== trim($registration->getEmailrep())) {
            $this->rateLimitTickNow($session, self::SESSION_RATE_KEY);

            return RegistrationResult::spam();
        }

        if (!$form->isValid()) {
            return RegistrationResult::invalid();
        }

        // Prepare meta-data
        $meta = (new FormSubmissionMetaEntity())
            ->setIp((string)($request->server->get('REMOTE_ADDR', '')))
            ->setUserAgent((string)($request->server->get('HTTP_USER_AGENT', '')))
            ->setTime(date('c'))
            ->setHost($request->getHost());
        $registration->setMeta($meta);

        // Try to persist to database (optional - continue if database is not available)
        try {
            $this->em->persist($registration);
            $this->em->flush();
        } catch (\Exception $dbException) {
            error_log('Registration form database error: ' . $dbException->getMessage());
        }

        // Send email (this is the critical part)
        try {
            $this->mailMan->sendRegistrationForm($registration);
        } catch (TransportExceptionInterface) {
            return RegistrationResult::mailError();
        }

        $this->rateLimitTickNow($session, self::SESSION_RATE_KEY);

        return RegistrationResult::success();
    }

    /**
     * The Ajax flow re-renders in place instead of redirecting, so there is
     * no snapshot to store.
     */
    protected function storeFormDataForRedirect(mixed $data): void
    {
    }
}
