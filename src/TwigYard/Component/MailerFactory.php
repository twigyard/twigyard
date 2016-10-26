<?php

namespace TwigYard\Component;

class MailerFactory
{

    /**
     * @var array
     */
    private $defaultSiteParameters;

    /**
     * @param array $defaultSiteParameters
     */
    public function __construct(array $defaultSiteParameters)
    {
        $this->defaultSiteParameters = $defaultSiteParameters;
    }

    /**
     * @param array $siteParameters
     * @return \TwigYard\Component\Mailer
     */
    public function createMailer(array $siteParameters)
    {
        $parameters = array_merge($this->defaultSiteParameters, $siteParameters);

        if (!empty($parameters['mailer_smtp_host'])) {
            $transport = \Swift_SmtpTransport::newInstance(
                $parameters['mailer_smtp_host'],
                $parameters['mailer_smtp_port'],
                $parameters['mailer_smtp_encryption']
            );

            $transport
                ->setUsername($parameters['mailer_smtp_user'])
                ->setPassword($parameters['mailer_smtp_password']);
        }

        $mailer = new Mailer(new \Swift_Mailer(isset($transport) ? $transport : \Swift_MailTransport::newInstance()));
        if (array_key_exists('debug_email', $parameters)) {
            $mailer->setDebugRecipient($parameters['debug_email']);
        }

        return $mailer;
    }
}
