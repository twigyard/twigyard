<?php

namespace TwigYard\Component;

class MailerFactory
{
    /**
     * @var MailerMessageBuilder
     */
    private $mailerMessageBuilder;

    /**
     * MailerFactory constructor.
     * @param MailerMessageBuilder $mailerMessageBuilder
     */
    public function __construct(MailerMessageBuilder $mailerMessageBuilder)
    {
        $this->mailerMessageBuilder = $mailerMessageBuilder;
    }

    /**
     * @param array $parameters
     * @return \TwigYard\Component\Mailer
     */
    public function createMailer(array $parameters): Mailer
    {
        if (!empty($parameters['smtp_host'])) {
            $transport = new \Swift_SmtpTransport(
                $parameters['smtp_host'],
                $parameters['smtp_port'],
                $parameters['smtp_encryption'] ?? null
            );
            $transport
                ->setUsername($parameters['smtp_username'] ?? null)
                ->setPassword($parameters['smtp_password'] ?? null);
        }

        $mailer = new Mailer(
            new \Swift_Mailer(isset($transport) ? $transport : new \Swift_SmtpTransport()),
            $this->mailerMessageBuilder
        );

        if (array_key_exists('debug_email', $parameters)) {
            $mailer->setDebugRecipient($parameters['debug_email']);
        }

        return $mailer;
    }
}
