<?php

namespace TwigYard\Component;

class MailerFactory
{
    /**
     * @var array
     */
    private $defaultSiteParameters;

    /**
     * @var MailerMessageBuilder
     */
    private $mailerMessageBuilder;

    /**
     * MailerFactory constructor.
     * @param array $defaultSiteParameters
     * @param MailerMessageBuilder $mailerMessageBuilder
     */
    public function __construct(array $defaultSiteParameters, MailerMessageBuilder $mailerMessageBuilder)
    {
        $this->defaultSiteParameters = $defaultSiteParameters;
        $this->mailerMessageBuilder = $mailerMessageBuilder;
    }

    /**
     * @param array $siteParameters
     * @return \TwigYard\Component\Mailer
     */
    public function createMailer(array $siteParameters): Mailer
    {
        $parameters = array_merge($this->defaultSiteParameters, $siteParameters);

        if (!empty($parameters['mailer_smtp_host'])) {
            $transport = new \Swift_SmtpTransport(
                $parameters['mailer_smtp_host'],
                $parameters['mailer_smtp_port'],
                $parameters['mailer_smtp_encryption']
            );

            $transport
                ->setUsername($parameters['mailer_smtp_user'])
                ->setPassword($parameters['mailer_smtp_password']);
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
