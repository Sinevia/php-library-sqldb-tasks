<?php

namespace Sinevia\Tasks;

abstract class BaseHandler {
    public $queuedTask = null;
    
    abstract handle($parameters){
    }
}
