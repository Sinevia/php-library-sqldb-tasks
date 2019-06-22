<?php

namespace Sinevia\Tasks;

abstract class BaseHandler {
    /**
     * @var Queue
     */
    public $queuedTask = null;
    
    abstract function handle(array $parameters);
}
