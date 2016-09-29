<?php

namespace TwigYard\Unit\Component;

use TwigYard\Component\Mailer;
use TwigYard\Component\MailerMessageBuilder;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class MailerCest
{
    /**
     * @param \UnitTester $I
     */
    public function testDebugRecipient(\UnitTester $I)
    {
        $prophet = new Prophet();
        $swiftMailerProph = $this->getSwiftMailer($prophet);

        $messageProph = $prophet->prophesize(\Swift_Message::class);
        $messageProph->setBcc([])->shouldBeCalled();
        $messageProph->setCc([])->shouldBeCalled();
        $messageProph->setTo(['developer@example.com' => 'developer'])->shouldBeCalled();

        $messageBuilderProph = $this->getMessageBuilderProph($prophet, $messageProph);

        $mailer = new Mailer($swiftMailerProph->reveal());
        $mailer->setDebugRecipient('developer@example.com');
        $mailer->send($messageBuilderProph->reveal());

        $prophet->checkPredictions();
    }

    /**
     * @param \UnitTester $I
     */
    public function testProductionRecipient(\UnitTester $I)
    {
        $prophet = new Prophet();
        $swiftMailerProph = $this->getSwiftMailer($prophet);

        $messageProph = $prophet->prophesize(\Swift_Message::class);
        $messageProph->setBcc([])->shouldNotBeCalled();
        $messageProph->setCc([])->shouldNotBeCalled();
        $messageProph->setTo(['developer@example.com' => 'developer'])->shouldNotBeCalled();

        $messageBuilderProph = $this->getMessageBuilderProph($prophet, $messageProph);

        $mailer = new Mailer($swiftMailerProph->reveal());
        $mailer->send($messageBuilderProph->reveal());

        $prophet->checkPredictions();
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getSwiftMailer(Prophet $prophet)
    {
        $swiftMailerProph = $prophet->prophesize(\Swift_Mailer::class);
        $swiftMailerProph->send(new TypeToken(\Swift_Message::class))->shouldBeCalled();

        return $swiftMailerProph;
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @param \Prophecy\Prophecy\ObjectProphecy $messageProph
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getMessageBuilderProph(Prophet $prophet, ObjectProphecy $messageProph)
    {
        $messageBuilderProph = $prophet->prophesize(MailerMessageBuilder::class);
        $messageBuilderProph->getMessage()->willReturn($messageProph->reveal());

        return $messageBuilderProph;
    }
}
