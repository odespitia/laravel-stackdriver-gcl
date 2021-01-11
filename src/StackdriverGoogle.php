<?php

namespace LaravelStackdriverGcl;


use Illuminate\Log\ParsesLogConfiguration;
use Monolog\Logger;

class StackdriverGoogle
{
    use ParsesLogConfiguration;

    public function __invoke($config)
    {
        return new Logger($this->parseChannel($config), [
            new StackdriverLogging(),
        ]);
    }

    protected function getFallbackChannelName()
    {
        return 'unknown';
    }
}