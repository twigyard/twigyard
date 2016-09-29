<?php

namespace TwigYard\Component;

class Mailer
{
    /**
     * @var \Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var string
     */
    private $debugRecipient;

    /**
     * Mailer constructor.
     * @param \Swift_Mailer $swiftMailer
     */
    public function __construct(\Swift_Mailer $swiftMailer)
    {
        $this->swiftMailer = $swiftMailer;
        $this->debugRecipient = null;
    }

    /**
     * @return \TwigYard\Component\MailerMessageBuilder
     */
    public function getMessageBuilder()
    {
        return new MailerMessageBuilder();
    }

    /**
     * @param MailerMessageBuilder $messageBuilder
     */
    public function send(MailerMessageBuilder $messageBuilder)
    {
        $message = $messageBuilder->getMessage();
        if ($this->debugRecipient !== null) {
            $message->setBcc([]);
            $message->setCc([]);
            $message->setTo([$this->debugRecipient => 'developer']);
        }
        
        $this->swiftMailer->send($message);
    }

    /**
     * @param string $email
     */
    public function setDebugRecipient($email)
    {
        $this->debugRecipient = $email;
    }
}
