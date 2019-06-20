<?php

namespace Sinevia\Tasks;

class Queue extends \Sinevia\ActiveRecord
{
    public static $keys = ['Id'];
    public static $table = 'snv_tasks_queue';

    public static $statusList = [
        'Cancelled' => 'Cancelled',
        'Completed' => 'Completed',
        'Deleted' => 'Deleted',
        'Failed' => 'Failed',
        'Paused' => 'Paused',
        'Processing' => 'Processing',
        'Queued' => 'Queued',
    ];

    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_DELETED = 'Deleted';
    const STATUS_FAILED = 'Failed';
    const STATUS_PAUSED = 'Paused';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_QUEUED = 'Queued';

    public function task()
    {
        $taskId = $this->get('TaskId');
        return Task::find($taskId);
    }

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

    public function appendDetails($message)
    {
        if (is_array($message) or is_object($message)) {
            $message = json_encode($message);
        }
        $details = $this->get('Details');
        $newDetails = $details . "\n" . date('Y-m-d H:i:s') . ' : ' . $message;
        $this->set('Details', $newDetails);
        $this->save();
        //$newDetails = "\n" . date('Y-m-d H:i:s') . ' : ' . $message;
        //file_put_contents(storage_path('logs/' . $this->Details), $newDetails, FILE_APPEND);
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
            ->column('TaskId', 'STRING')
            ->column('TaskAlias', 'STRING')
            ->column('LinkedIds', 'STRING')
            ->column('Parameters', 'TEXT')
            ->column('Output', 'TEXT')
            ->column('Details', 'TEXT')
            ->column('Attempts', 'INTEGER')
            ->column('StartedAt', 'DATETIME', 'DEFAULT NULL')
            ->column('CompletedAt', 'DATETIME', 'DEFAULT NULL')
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

    public function getParameters()
    {
        $parameters = json_decode($this->get('Parameters'), true);
        if ($parameters == false) {
            return [];
        }
        return $parameters;
    }

    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();
        $parameters[$key] = $value;
        $this->setParameters($parameters);
    }
    public function setParameters($parameters)
    {
        $this->set('Parameters', json_encode($parameters, JSON_PRETTY_PRINT));
        $this->save();
    }

    public function getParameter($key)
    {
        $parameters = $this->getParameters();
        if (isset($parameters[$key])) {
            return $parameters[$key];
        }
        return null;
    }
    public function getOutput()
    {
        return json_decode($this->get('Output'), true);
    }
    public function setOutput($ouput)
    {
        $this->set('Output', json_encode($ouput, JSON_PRETTY_PRINT));
        $this->save();
    }
    public function getOutputKey($key)
    {
        $output = $this->getOutput();
        if (isset($output[$key])) {
            return $output[$key];
        }
        return null;
    }
    public function setOutputKey($key, $value)
    {
        $output = $this->getOutput();
        $output[$key] = $value;
        $this->setOutput($output);
    }
    public function fail($message = 'Failed')
    {
        if ($message != null) {
            $this->appendDetails($message);
        }
        $this->set('Status', static::STATUS_FAILED);
        $this->set('CompletedAt', date('Y-m-d H:i:s'));
        $this->save();
    }
    public function complete($message = 'Success')
    {
        if ($message != null) {
            $this->appendDetails($message);
        }
        $this->set('Status', static::STATUS_COMPLETED);
        $this->set('CompletedAt', date('Y-m-d H:i:s'));
        $this->save();
    }
    public function processing($message = 'Processing')
    {
        if ($message != null) {
            $this->appendDetails($message);
        }
        $this->set('Status', static::STATUS_PROCESSING);
        $this->set('StartedAt', date('Y-m-d H:i:s'));
        $this->save();
    }
    
    public static function process($queuedId)
    {
        $queued = static::find($queuedId);

        $status = $queued->get('Status');

        if ($status != static::STATUS_QUEUED) {
            $queued->fail('Not Queued and CANNOT process');
            return fasle;
        }

        $className = $queued->get('TaskAlias');

        if (class_exists($className) == false) {
            $queued->fail('Class "' . $className . '" does not exist');
            return false;
        }

        $classInstance = new $className;

        if (method_exists($classInstance, 'handle') == false) {
            $queued->fail('Method "handle" does not exist in class "' . $className . '"');
            return false;
        }
        
        $classInstance->queuedTask = $queued;

        try {
            $result = $classInstance->handle($queued->getParameters());
            if ($result == false) {
                $queued->fail();
                return false;
            } else {
                $queued->complete();
                return true;
            }
        } catch (\Exception $e) {
            $queued->fail($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
            //$queued->fail($e->getTraceAsString());
            return false;
        }

    }
}
