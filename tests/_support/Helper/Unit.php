<?php

namespace Helper;

use Codeception\Module;

class Unit extends Module
{
    /**
     * @param string $exception
     * @param callable $callback
     */
    public function seeExceptionThrown($exception, $callback)
    {
        $function = function () use ($callback, $exception) {
            try {
                $callback();

                return false;
            } catch (\Exception $e) {
                if (get_class($e) === $exception or get_parent_class($e) === $exception) {
                    return true;
                }
//                throw $e;
            }
        };

        $this->assertTrue($function());
    }
}
