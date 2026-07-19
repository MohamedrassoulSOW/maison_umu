<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Service\BrandLogo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        LoggerInterface $logger,
        BrandLogo $brandLogo,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailAddress = mb_strtolower(trim((string) $form->get('email')->getData()));
            $user = $userRepository->findOneBy(['email' => $emailAddress]);

            // Fallback: case-insensitive search if exact match fails
            if (!$user) {
                $user = $userRepository->createQueryBuilder('u')
                    ->where('LOWER(u.email) = :email')
                    ->setParameter('email', $emailAddress)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            }

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $entityManager->flush();

                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $html = $this->renderView('mail/reset_password.html.twig', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                ]);

                $fromEmail = (string) $this->getParameter('mailer.from_email');
                $fromName = (string) $this->getParameter('mailer.from_name');

                try {
                    $emailMessage = (new Email())
                        ->from(new Address($fromEmail, $fromName))
                        ->replyTo($fromEmail)
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe — Maison UMU')
                        ->html($html)
                        ->text(sprintf(
                            "Bonjour %s,\n\nRéinitialisez votre mot de passe via ce lien (valable 1h) :\n%s\n",
                            $user->getFirstName() ?? '',
                            $resetUrl
                        ));

                    $logoPath = $brandLogo->getPath();
                    if ($logoPath) {
                        $emailMessage->embedFromPath($logoPath, BrandLogo::CID);
                    }

                    $mailer->send($emailMessage);
                    $logger->info('Password reset mail sent', [
                        'to' => $user->getEmail(),
                        'from' => $fromEmail,
                    ]);
                } catch (\Throwable $e) {
                    $logger->error('Password reset mail failed: '.$e->getMessage(), [
                        'to' => $user->getEmail(),
                        'from' => $fromEmail,
                        'exception' => $e,
                    ]);
                }
            }

            // Same message whether the account exists or not (no email enumeration)
            $this->addFlash(
                'success',
                'Si un compte existe avec cet email, un lien de réinitialisation vient d’être envoyé. Vérifiez aussi vos indésirables.'
            );

            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('security/reset_password_request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid($token)) {
            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->clearResetToken();
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été mis à jour. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form,
        ]);
    }
}
