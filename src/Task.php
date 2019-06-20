<?php

namespace Sinevia\Tasks;


class Task extends \Sinevia\ActiveRecord
{
    public static $keys = ['Id'];
    public static $table = 'snv_tasks_task';

    public static $statusList = [
        'Active' => 'Active',
        'Disabled' => 'Disabled',
    ];

    const STATUS_ACTIVE = 'Active';
    const STATUS_DISABLED = 'Disabled';

    public static function getTable()
    {
        return static::getDatabase()->table(static::getTableName());
    }

    public static function getDatabase()
    {
        return db();
    }

    public function beforeInsert()
    {
        try {
            $this->get('Id');
        } catch (\Exception $e) {
            $this->set('Id', \Sinevia\Uid::microUid());
        }
        $this->set('CreatedAt', date('Y-m-d H:i:s'));
        $this->set('UpdatedAt', date('Y-m-d H:i:s'));
    }

    public function beforeUpdate()
    {
        $this->set('UpdatedAt', date('Y-m-d H:i:s'));
    }

    public static function findByAlias($taskAlias)
    {
        $result = static::getTable()->where('Alias', '=', $taskAlias)->selectOne();

        if ($result === null) {
            return null;
        }

        $task = new Task();
        $task->data = $result;
        return $task;
    }

    public static function queue($taskAlias, $parameters = [], $linkedIds = [])
    {
        $task = Task::findByAlias($taskAlias);
        //$id = \Sinevia\Uid::microUid();

        $queuedTask = new Queue;
        //$queuedTask->set('Id', $id);
        $queuedTask->set('TaskId', is_null($task) ? '' : $task->get("Id"));
        $queuedTask->set('TaskAlias', $taskAlias);
        $queuedTask->set('Status', 'Queued');
        $queuedTask->set('Parameters', json_encode($parameters));
        $queuedTask->set('LinkedIds', json_encode($linkedIds));
        $queuedTask->set('Attempts', 0);
        $queuedTask->set('Details', '');
        //$queuedTask->set('Details', $id . '.task.log.txt');
        $queuedTask->set('Output', json_encode([]));
        $queuedTask->save();

        return $queuedTask;
    }

    /**
     * Create table
     *
     * @return void
     */
    public static function tableCreate()
    {
        if (static::getDatabase()->table(static::$table)->exists()) {
            echo "Table '" . static::$table . "' already exists skipped...";
            return true;
        }

        static::getDatabase()->table(static::$table)
            ->column('Id', 'STRING')
            ->column('Status', 'STRING')
            ->column('Alias', 'STRING')
            ->column('Title', 'STRING')
            ->column('Description', 'TEXT')
            ->column('Memo', 'TEXT')
            ->column('CreatedAt', 'DATETIME', 'DEFAULT NULL')
            ->column('UpdatedAt', 'DATETIME', 'DEFAULT NULL')
            ->column('DeletedAt', 'DATETIME', 'DEFAULT NULL')
            ->create();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public static function tableDelete()
    {
        if (static::getDatabase()->table(static::$table)->exists() == false) {
            echo "Table '" . static::$table . "' already exists deleted...";
            return true;
        }
        static::getDatabase()->table(static::$table)->drop();
    
    }

}
