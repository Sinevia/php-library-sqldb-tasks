<?php
define("ENVIRONMENT", 'testing');
require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Returns a database instance
 * @return \Sinevia\SqlDb
 */
function db()
{
    static $db = null;

    if (is_null($db)) {
        $db = new \Sinevia\SqlDb(array(
            'database_type' => 'sqlite',
            'database_host' => ":memory:",
            'database_name' => ":memory:",
            'database_user' => "test",
            'database_pass' => "",
        ));
    }

    return $db;
}

//\App\Models\Content\Node::getDatabase()->debug = true;

$tf = new \Testify\Testify("My Test Suite");

$tf->beforeEach(function ($tf) {
    \Sinevia\Tasks\Task::tableCreate();
    \Sinevia\Tasks\Queue::tableCreate();
});

$tf->test("Testing tasks", function ($tf) {
    db()->debug = true;
    $task = new \Sinevia\Tasks\Task();
    $task->set('Alias', 'TEST');
    $task->save();

    $queuedTask = $task->queue('TEST');
    $tf->assertTrue(is_object($task));
    $tf->assertTrue(is_object($queuedTask));

    $queuedTask->processing();
    $tf->assertEquals($queuedTask->get('Status'), \Sinevia\Tasks\Queue::STATUS_PROCESSING);

    $queuedTask->complete();
    $tf->assertEquals($queuedTask->get('Status'), \Sinevia\Tasks\Queue::STATUS_COMPLETED);

    $queuedTask->fail();
    $tf->assertEquals($queuedTask->get('Status'), \Sinevia\Tasks\Queue::STATUS_FAILED);

    db()->debug = false;
});

$tf->test("Testing task queue", function ($tf) {
    db()->debug = true;
    $task = new \Sinevia\Tasks\Task();
    $task->set('Alias', 'App\Task\TestTask');
    $task->save();

    $queuedTask = $task->queue('App\Task\TestTask');
    $tf->assertTrue(is_object($task));
    $tf->assertTrue(is_object($queuedTask));

    $tf->assertEquals($queuedTask->get('Status'), \Sinevia\Queue::STATUS_QUEUED);

    \Sinevia\Queue::process($queuedTask->get('Id'));
    db()->debug = false;
});

$tf();

class TestTask {
    
    public $queuedTask = null;
    
    function handle($parameters = []) {
        $queuedTask->setParameter('test', 'successful');
    }
}

