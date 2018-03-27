<?php

namespace TwigYard\Middleware\Form\Handler;

interface HandlerInterface
{
    /**
     * @param array $formData
     */
    public function handle(array $formData): void;
}
