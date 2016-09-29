<?php

namespace TwigYard\Component;

class MailerFactory
{

    /**
     * @var bool
     */
    private $debugEmailEnabled;

    /**
     * @var array
     */
    private $defaultSiteParameters;

    /**
     * @param array $defaultSiteParameters
     * @param bool $debugEmailEnabled
     */
    public function __construct(array $defaultSiteParameters, $debugEmailEnabled)
    {
        $this->debugEmailEnabled = $debugEmailEnabled;
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
        if ($this->debugEmailEnabled) {
            $mailer->setDebugRecipient($parameters['debug_email']);
        }

        return $mailer;
    }
}
