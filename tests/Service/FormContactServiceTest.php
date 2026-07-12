<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\SubmissionStatus;
use App\Entity\FormContactEntity;
use App\Service\FormContactService;
use App\Service\MailManService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Validator\Validation;

class FormContactServiceTest extends TestCase
{
    public function testHandleReturnsNullForGetRequest(): void
    {
        $stack = $this->makeStack(new Request());

        $svc = $this->makeService($stack);

        $this->assertNull($svc->handle());
    }

    public function testHandleReturnsInvalidForEmptySubmission(): void
    {
        $stack = $this->makeStack($this->makePostRequest(['name' => '']));

        $svc = $this->makeService($stack);
        $result = $svc->handle();

        $this->assertNotNull($result);
        $this->assertSame(SubmissionStatus::INVALID, $result->status);
        $this->assertTrue($svc->getForm()->isSubmitted());
        $this->assertFalse($svc->getForm()->isValid());
    }

    public function testHandleReturnsSpamWhenHoneypotFilled(): void
    {
        $mailMan = $this->createMock(MailManService::class);
        $mailMan->expects($this->never())->method('sendContactForm');

        $data = $this->validData();
        $data['website'] = 'http://spam.example.com';
        $stack = $this->makeStack($this->makePostRequest($data));

        $svc = $this->makeService($stack, $mailMan);
        $result = $svc->handle();

        $this->assertNotNull($result);
        $this->assertSame(SubmissionStatus::SPAM, $result->status);
    }

    public function testHandleReturnsSuccessAndPersistsAndMails(): void
    {
        $mailMan = $this->createMock(MailManService::class);
        $mailMan->expects($this->once())
            ->method('sendContactForm')
            ->with($this->isInstanceOf(FormContactEntity::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(FormContactEntity::class));
        $em->expects($this->once())->method('flush');

        $stack = $this->makeStack($this->makePostRequest($this->validData()));

        $svc = $this->makeService($stack, $mailMan, $em);
        $result = $svc->handle();

        $this->assertNotNull($result);
        $this->assertSame(SubmissionStatus::SUCCESS, $result->status);
    }

    public function testHandleReturnsMailErrorWhenTransportFails(): void
    {
        $mailMan = $this->createMock(MailManService::class);
        $mailMan->method('sendContactForm')
            ->willThrowException(new TransportException('SMTP down'));

        $stack = $this->makeStack($this->makePostRequest($this->validData()));

        $svc = $this->makeService($stack, $mailMan);
        $result = $svc->handle();

        $this->assertNotNull($result);
        $this->assertSame(SubmissionStatus::MAIL_ERROR, $result->status);
    }

    public function testSecondSubmissionWithinIntervalIsRateLimited(): void
    {
        $session = $this->makeSession();

        $stack = $this->makeStack($this->makePostRequest($this->validData(), $session));
        $result = $this->makeService($stack)->handle();
        $this->assertNotNull($result);
        $this->assertSame(SubmissionStatus::SUCCESS, $result->status);

        $stack2 = $this->makeStack($this->makePostRequest($this->validData(), $session));
        $result2 = $this->makeService($stack2)->handle();
        $this->assertNotNull($result2);
        $this->assertSame(SubmissionStatus::RATE_LIMITED, $result2->status);
    }

    /**
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'name'    => 'John Doe',
            'email'   => 'john@example.com',
            'phone'   => '+49 123 456789',
            'message' => 'This is a valid test message.',
            'consent' => '1',
        ];
    }

    private function makeSession(): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return $session;
    }

    /**
     * @param array<string, string> $data
     */
    private function makePostRequest(array $data, ?Session $session = null): Request
    {
        $request = Request::create('/kontakt', 'POST', ['form_contact' => $data]);
        $request->setSession($session ?? $this->makeSession());

        return $request;
    }

    private function makeStack(Request $request): RequestStack
    {
        if (!$request->hasSession()) {
            $request->setSession($this->makeSession());
        }

        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }

    private function makeFormFactory(): FormFactoryInterface
    {
        // No CSRF extension in unit tests; HttpFoundation extension lets the
        // form read submitted data straight from the Request object.
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
    }

    private function makeService(
        RequestStack $stack,
        ?MailManService $mailMan = null,
        ?EntityManagerInterface $em = null,
    ): FormContactService {
        return new FormContactService(
            $this->makeFormFactory(),
            $stack,
            $mailMan ?? $this->createMock(MailManService::class),
            $em ?? $this->createMock(EntityManagerInterface::class),
        );
    }
}
