<?php

namespace TwigYard\Component;

class Mailer
{
    /**
     * @var \Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var MailerMessageBuilder
     */
    private $mailerMessageBuilder;

    /**
     * @var string
     */
    private $debugRecipient;

    /**
     * Mailer constructor.
     * @param \Swift_Mailer $swiftMailer
     * @param MailerMessageBuilder $mailerMessageBuilder
     */
    public function __construct(\Swift_Mailer $swiftMailer, MailerMessageBuilder $mailerMessageBuilder)
    {
        $this->swiftMailer = $swiftMailer;
        $this->mailerMessageBuilder = $mailerMessageBuilder;
        $this->debugRecipient = null;
    }

    /**
     * @return \TwigYard\Component\MailerMessageBuilder
     */
    public function getMessageBuilder(): MailerMessageBuilder
    {
        return $this->mailerMessageBuilder;
    }

    /**
     * @param MailerMessageBuilder $messageBuilder
     */
    public function send(MailerMessageBuilder $messageBuilder): void
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
    public function setDebugRecipient(string $email): void
    {
        $this->debugRecipient = $email;
    }
}
