<?php

namespace TwigYard\Component;

use Html2Text\Html2Text;
use Swift_Message;

class MailerMessageBuilder
{
    /**
     * @var Swift_Message
     */
    private $message;

    /**
     * MailerMessage constructor.
     */
    public function __construct()
    {
        $this->message = new Swift_Message();
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody(string $body): MailerMessageBuilder
    {
        $this->message->setBody($body, 'text/html');
        $this->message->addPart((new Html2Text($body))->getText(), 'text/plain');
        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): MailerMessageBuilder
    {
        $this->message->setSubject($subject);
        return $this;
    }

    /**
     * @param array $toAddresses
     * @return $this
     */
    public function setTo(array $toAddresses): MailerMessageBuilder
    {
        $this->message->setTo($toAddresses);
        return $this;
    }

    /**
     * @param array $fromArray
     * @return $this
     */
    public function setFrom(array $fromArray): MailerMessageBuilder
    {
        $this->message->setFrom($fromArray);
        return $this;
    }

    /**
     * @param array $bccAddresses
     * @return $this
     */
    public function setBcc(array $bccAddresses): MailerMessageBuilder
    {
        $this->message->setBcc($bccAddresses);
        return $this;
    }

    /**
     * @return Swift_Message
     */
    public function getMessage(): Swift_Message
    {
        return $this->message;
    }
}
