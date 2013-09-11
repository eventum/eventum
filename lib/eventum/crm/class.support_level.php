<?php

/**
 * Represents a support level
 */
abstract class Support_Level
{
    /**
     * Holds the parent CRM object.
     *
     * @var CRM
     */
    protected $crm;

    /**
     * Holds the database connection this object should use.
     *
     * @var resource
     */
    protected $connection;

    protected $level_id;

    protected $name;

    protected $description;

    protected $maximum_response_time;

    public function __construct(CRM &$crm, $level_id)
    {
        $this->crm =& $crm;
        $this->connection =& $crm->getConnection();
        $this->level_id = $level_id;

        $this->load();
    }


    abstract protected function load();

    public function getName()
    {
        return $this->name;
    }

    public function getLevelID()
    {
        return $this->level_id;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getMaximumResponseTime()
    {
        return $this->maximum_response_time;
    }


}


class SupportLevelNotFoundException extends CRMException
{
    public function __construct($level_id, Exception $previous=null) {
        parent::__construct("Support Level '" . $level_id. "' not found", 0, $previous);
    }
}