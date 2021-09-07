<?php

namespace OSR\Training\Provider;

use Training;

class HelloWorldProvider
{
    /**
     * @var training
     */
    private $module;

    public function __construct(training $module)
    {
        $this->module = $module;
    }

    public function getHelloWorld()
    {
        return $this->module->l('hello world');
    }
}