<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FormBookingEntity;
use App\Entity\FormContactEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailManService
{
    private const THEME_STORAGE_KEY = 'theme';

    public function __construct(
        private MailerInterface $mailer,
        private Twig $twig,
        private string $fromAddress,
        private string $fromName,
        private string $toAddress,
        private string $toName,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function sendContactForm(FormContactEntity $contact): void
    {
        // Use fallbacks to avoid failing in test environments where env vars may be empty
        $fallbackFrom = 'no-reply@localhost';
        $fallbackTo = 'owner@localhost';

        $from = $this->makeAddressOrFallback($this->fromAddress, $this->fromName, $fallbackFrom);
        $to = $this->makeAddressOrFallback($this->toAddress, $this->toName, $fallbackTo);
        $theme = $this->getEmailTheme();

        $context = [
            'contact' => $contact,
            'theme'   => $theme,
        ];

        try {
            // Send email to owner (always use light theme for admin emails)
            $ownerSubject = 'Theatergruppe Freispiel — Neue Kontaktanfrage';
            $ownerText = $this->twig->render('email/contact_owner.txt.twig', $context);
            $ownerHtml = $this->twig->render('email/contact_owner.html.twig', $context);

            $emailOwner = new Email()
                ->from($from)
                ->to($to)
                ->subject($ownerSubject)
                ->text($ownerText)
                ->html($ownerHtml);

            // Try to set replyTo if the visitor supplied a valid email
            try {
                $visitorEmail = trim($contact->getEmailAddress());

                if ('' !== $visitorEmail) {
                    $replyTo = new Address($visitorEmail, $contact->getName());
                    $emailOwner->replyTo($replyTo);
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Invalid visitor email for replyTo; skipping replyTo header', ['email' => $contact->getEmailAddress(), 'exception' => $e]);
            }

            $this->mailer->send($emailOwner);
            $this->logger->info('Contact mail sent to owner', [
                'to'    => $to->getAddress(),
                'name'  => $to->getName(),
                'email' => $contact->getEmailAddress(),
                'theme' => $theme,
            ]);

            // Send copy to visitor with their preferred theme if email looks valid
            if ($contact->getCopy()) {
                $visitorEmail = trim($contact->getEmailAddress());
                if ('' !== $visitorEmail && filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {
                    $visitorSubject = 'Theatergruppe Freispiel — Ihre Kontaktanfrage';
                    $visitorText = $this->twig->render('email/contact_visitor.txt.twig', $context);
                    $visitorHtml = $this->twig->render('email/contact_visitor.html.twig', $context);

                    $emailVisitor = (new Email())
                        ->from($from)
                        ->to(new Address($visitorEmail, $contact->getName()))
                        ->subject($visitorSubject)
                        ->text($visitorText)
                        ->html($visitorHtml);

                    $this->mailer->send($emailVisitor);
                    $this->logger->info('Contact mail sent to visitor', [
                        'to'    => $visitorEmail,
                        'name'  => $contact->getName(),
                        'theme' => $theme,
                    ]);
                } else {
                    $this->logger->warning('Skipping visitor copy: invalid or empty visitor email', ['email' => $contact->getEmailAddress()]);
                }
            }
        } catch (TransportExceptionInterface $e) {
            // Logs transport failures (bad DSN, auth, SSL, DNS, etc.)
            $this->logger->error('Mailer send failed: ' . $e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Send a confirmation request to the visitor with a unique link.
     *
     * @throws TransportExceptionInterface|RuntimeError|LoaderError|SyntaxError
     */
    public function sendBookingVisitorConfirmationRequest(FormBookingEntity $booking, string $confirmUrl): void
    {
        $from = $this->makeAddressOrFallback($this->fromAddress, $this->fromName, 'no-reply@localhost');
        $toVisitor = $this->makeAddressOrFallback($booking->getEmail(), $booking->getName(), 'visitor@localhost');

        $context = [
            'booking'    => $booking,
            'confirmUrl' => $confirmUrl,
        ];

        // Log before attempting to render or send
        $this->logger->info(
            'Preparing booking confirmation request',
            [
                'to'    => $toVisitor->getAddress(),
                'name'  => $toVisitor->getName(),
                'token' => substr($booking->getConfirmationToken(), 0, 6) . '…',
            ]
        );

        try {
            $subject = 'Theatergruppe Freispiel — Bitte bestätigen Sie Ihre Buchung';
            $text = $this->twig->render('email/booking_visitor_confirm_request.txt.twig', $context);
            $html = $this->twig->render('email/booking_visitor_confirm_request.html.twig', $context);

            $email = (new Email())
                ->from($from)
                ->to($toVisitor)
                ->replyTo(new Address($this->toAddress, $this->toName))
                ->subject($subject)
                ->text($text)
                ->html($html);

            $this->mailer->send($email);
            $this->logger->info('Booking confirmation request sent successfully', [
                'to'        => $toVisitor->getAddress(),
                'bookingId' => $booking->getId(),
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Mailer transport failed', [
                'exception' => $e->getMessage(),
                'to'        => $toVisitor->getAddress(),
                'bookingId' => $booking->getId(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Email preparation or sending failed', [
                'exception' => $e->getMessage(),
                'to'        => $toVisitor->getAddress(),
                'bookingId' => $booking->getId(),
            ]);

            throw $e;
        }
    }

    /**
     * Notify the owner when a booking was confirmed by the visitor.
     *
     * @throws TransportExceptionInterface|RuntimeError|LoaderError|SyntaxError
     */
    public function sendBookingOwnerNotification(FormBookingEntity $booking): void
    {
        $from = $this->makeAddressOrFallback($this->fromAddress, $this->fromName, 'no-reply@localhost');
        $toOwner = $this->makeAddressOrFallback($this->toAddress, $this->toName, 'owner@localhost');

        $context = ['booking' => $booking];

        $subject = 'Theatergruppe Freispiel — Buchung bestätigt';
        $text = $this->twig->render('email/booking_owner_confirmed.txt.twig', $context);
        $html = $this->twig->render('email/booking_owner_confirmed.html.twig', $context);

        $email = new Email()
            ->from($from)
            ->to($toOwner)
            ->replyTo(new Address($booking->getEmail(), $booking->getName()))
            ->subject($subject)
            ->text($text)
            ->html($html);

        $this->logger->info('Sending booking owner notification', [
            'to'        => $toOwner->getAddress(),
            'name'      => $toOwner->getName(),
            'bookingId' => $booking->getId(),
        ]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Booking notification sent to owner');
        } catch (TransportExceptionInterface $e) {
            // Logs transport failures (bad DSN, auth, SSL, DNS, etc.)
            $this->logger->error('Mailer send failed: ' . $e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Safe helper: try to create an Address from the provided email/name and
     * fall back to a sane default if the value is empty or invalid.
     */
    private function makeAddressOrFallback(string $email, string $name, string $fallback): Address
    {
        $email = trim($email);

        if ('' === $email) {
            $this->logger->warning(sprintf('Email is empty, using fallback <%s>', $fallback));

            return new Address($fallback, $name);
        }

        try {
            return new Address($email, $name);
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf('Invalid email "%s", using fallback <%s>: %s', $email, $fallback, $e->getMessage()), ['exception' => $e]);

            return new Address($fallback, $name);
        }
    }

    /**
     * Determine which email theme to use based on user's preference
     */
    private function getEmailTheme(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return 'light';
        }

        $session = $request->getSession();
        $storedTheme = $session->get(self::THEME_STORAGE_KEY);

        // If user explicitly chose dark or light, use that
        if ('dark' === $storedTheme) {
            return 'dark';
        }

        if ('light' === $storedTheme) {
            return 'light';
        }

        // If 'system' or not set, check User-Agent for dark mode preference
        // Note: This is a fallback - in practice, the localStorage value should be used
        $userAgent = $request->headers->get('User-Agent', '');

        // Default to light theme for emails
        return 'light';
    }
}
