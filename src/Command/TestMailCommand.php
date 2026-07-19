<?php

namespace App\Command;

use App\Service\BrandLogo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:mail:test',
    description: 'Envoie un email de test (Hostinger / production)',
)]
final class TestMailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        private BrandLogo $brandLogo,
        #[Autowire('%mailer.from_email%')]
        private string $fromEmail,
        #[Autowire('%mailer.from_name%')]
        private string $fromName,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Adresse de destination');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = (string) $input->getArgument('to');

        $io->title('Test mailer Maison UMU');
        $io->listing([
            'From: '.$this->fromName.' <'.$this->fromEmail.'>',
            'To: '.$to,
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->replyTo($this->fromEmail)
            ->to($to)
            ->subject('Test mailer — Maison UMU')
            ->html(
                '<p>Bonjour,</p>'.
                '<p>Ceci est un email de test depuis <strong>Maison UMU</strong>.</p>'.
                '<p>Si vous recevez ce message, le SMTP fonctionne (reset + commandes OK).</p>'
            )
            ->text("Test mailer Maison UMU — le SMTP fonctionne.");

        $logoPath = $this->brandLogo->getPath();
        if ($logoPath) {
            $email->embedFromPath($logoPath, BrandLogo::CID);
        }

        try {
            $this->mailer->send($email);
            $io->success('Email envoyé avec succès à '.$to);
            $io->note('Vérifiez aussi les indésirables / spam.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Échec envoi : '.$e->getMessage());
            $io->writeln('Vérifiez MAILER_DSN, MAILER_FROM_EMAIL (doit être la boîte SMTP Hostinger) et le mot de passe email.');

            return Command::FAILURE;
        }
    }
}
