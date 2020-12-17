<?php

namespace TwigYard\Middleware\Form\Handler;

use Zend\Diactoros\Response;

interface HandlerInterface
{
    /**
     * @return Response|void|null
     */
    public function handle(array $formData);
}
