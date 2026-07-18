<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $fromEmail = (string) $this->getParameter('mailer.from_email');
                $fromName = (string) $this->getParameter('mailer.from_name');

                $email = (new Email())
                    ->from(new Address($fromEmail, $fromName))
                    ->replyTo($data['email'])
                    ->to($fromEmail)
                    ->subject('[Maison UMU] '.$data['subject'])
                    ->text(sprintf(
                        "Nouveau message depuis le site Maison UMU\n\nNom : %s\nEmail : %s\nSujet : %s\n\n%s",
                        $data['name'],
                        $data['email'],
                        $data['subject'],
                        $data['message']
                    ));

                $mailer->send($email);
                $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons rapidement.');
            } catch (\Throwable) {
                $this->addFlash('danger', 'Impossible d’envoyer le message pour le moment. Réessayez plus tard.');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('page/contact.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
