<?php
define("ENVIRONMENT", 'testing');
require dirname(__DIR__) . '/vendor/autoload.php';

/*
 * Settings here or get overwritten
 */
\Sinevia\Registry::set("ENVIRONMENT", "testing");
\Sinevia\Registry::set("DB_TYPE", "sqlite");
\Sinevia\Registry::set("DB_HOST", ":memory:");
\Sinevia\Registry::set("DB_NAME", ":memory:");
\Sinevia\Registry::set("DB_USER", "test");
\Sinevia\Registry::set("DB_PASS", "");
include dirname(__DIR__) . '/router.php';

function get($path, $data = [])
{
    $_REQUEST = $data;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    return main();
}

function post($path, $data = [])
{
    $_REQUEST = $data;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    return main();
}

\Sinevia\Migrate::setDatabase(db());
//\App\Models\Content\Node::getDatabase()->debug = true;

$tf = new \Testify\Testify("My Test Suite");

$tf->beforeEach(function ($tf) {
    \Sinevia\Migrate::setDirectoryMigration(\Sinevia\Registry::get('DIR_MIGRATIONS_DIR'));
    \Sinevia\Migrate::setDatabase(db());
    \Sinevia\Migrate::$verbose = false;
    \Sinevia\Migrate::up();
});

$tf->test("Testing tasks", function ($tf) {
    //db()->debug = true;
    $task = new \App\Models\Tasks\Task();
    $task->set('Alias', 'TEST');
    $task->save();

    $queuedTask = $task->queue('TEST');
    $tf->assertTrue(is_object($task));
    $tf->assertTrue(is_object($queuedTask));

    $queuedTask->processing();
    $tf->assertEquals($queuedTask->get('Status'), \App\Models\Tasks\Queue::STATUS_PROCESSING);

    $queuedTask->complete();
    $tf->assertEquals($queuedTask->get('Status'), \App\Models\Tasks\Queue::STATUS_COMPLETED);

    $queuedTask->fail();
    $tf->assertEquals($queuedTask->get('Status'), \App\Models\Tasks\Queue::STATUS_FAILED);

    //db()->debug = false;
});

$tf->test("Testing task queue", function ($tf) {
    db()->debug = true;
    $task = new \App\Models\Tasks\Task();
    $task->set('Alias', 'App\Task\TestTask');
    $task->save();

    $queuedTask = $task->queue('App\Task\TestTask');
    $tf->assertTrue(is_object($task));
    $tf->assertTrue(is_object($queuedTask));

    $tf->assertEquals($queuedTask->get('Status'), \App\Models\Tasks\Queue::STATUS_QUEUED);

    \App\Helpers\Queue::process($queuedTask->get('Id'));
    db()->debug = false;
});

$tf();

