<?php

namespace TwigYard\Middleware\Form\Handler;

use Zend\Diactoros\Response;

interface HandlerInterface
{
    /**
     * @param array $formData
     * @return Response|null
     */
    public function handle(array $formData);
}
