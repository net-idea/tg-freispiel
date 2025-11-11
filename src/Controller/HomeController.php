<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check honeypot field - if filled, it's likely a bot
            $honeypot = $form->get('website')->getData();
            if (!empty($honeypot)) {
                // Silently reject spam without showing error
                $this->addFlash('success', 'Vielen Dank für Ihre Nachricht! Wir werden uns bald bei Ihnen melden.');
                return $this->redirectToRoute('contact');
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Vielen Dank für Ihre Nachricht! Wir werden uns bald bei Ihnen melden.');

            return $this->redirectToRoute('contact');
        }

        return $this->render('home/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
