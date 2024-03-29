# PHP Tasks for SqlDb

Persistent task queue for SqlDb.

[![Build status][build-status-master-image]][build-status-master]
[![GitHub stars](https://img.shields.io/github/stars/Sinevia/php-library-sqldb-tasks.svg?style=social&label=Star&maxAge=2592000)](https://GitHub.com/Sinevia/php-library-sqldb-tasks/stargazers/)
[![HitCount](http://hits.dwyl.io/Sinevia/badges.svg)](http://hits.dwyl.io/Sinevia/badges)

[build-status-master]: https://travis-ci.com/Sinevia/php-library-sqldb-tasks
[build-status-master-image]: https://api.travis-ci.com/Sinevia/php-library-sqldb-tasks.svg?branch=master

## Features ##

- Asynchronious (multiple threads/processes) or synchronious (sigle thread/process) execution
- Instantant (by the code creating the task) or queued for execution by another process
- Full details logged
- Inspectable, debuggable
- Re-runnable

## How it works? ##

- *Tasks.* Tasks are defined in the task table. Each task defines a handler class. The handler class has a method handle($parameters), which processes teh task and returns true on success, false otherwise.

- *Queue.* The tasks to be processed are added to the queued table with its parameters. Each task is then sequentially processed, by calling the handle methdod of its handler which also receives the parameters. Depending on the result the queued task is marked as completed on success, failed otherwise.

## Quick Example ##

```php
$task = \Sinevia\Tasks\Task::queue('\App\Tasks\PaypalOrderTask', $parametersArray);

if ($task == null){
    logger()->error('Task "\App\Tasks\PaypalOrderTask" failed to be created', $parametersArray);
    return false;
}

$result = \Sinevia\Tasks\Queue::processQueuedTaskById($task->get('Id'));
return $result;
```

## Task Handlers ##

```
class HelloWorldTask extends \Sinevia\Tasks\BaseHandler {
    function handle(array $parameters){
        $this->queuedTask->addDetails('Adding Hello World to Output Parameters');
        
        $this->queuedTask->setOutputKey('hello','world');
        
        return true;
    }
}
```
