<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Eventum\Db\DatabaseException;

/**
 * Abstract class representing a customer
 */
abstract class Customer
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
     * @var MDB2_Driver_Common
     */
    protected $connection;

    /**
     * The ID of the customer this object represents
     *
     * @var string
     */
    protected $customer_id;

    /**
     * The name of the customer this object represents
     *
     * @var string
     */
    protected $name;

    /**
     * The country of the customer this object represents
     *
     * @var string
     */
    protected $country;

    /**
     * The account manager
     *
     * @var string
     */
    protected $account_manager;

    /**
     * Constructs the customer object and loads customer and support option data.
     *
     * @param CRM $crm
     * @param string $customer_id
     * @throws CustomerNotFoundException
     * @see Customer::load();
     */
    public function __construct(CRM $crm, $customer_id)
    {
        $this->crm = $crm;
        $this->connection = &$crm->getConnection();
        $this->customer_id = $customer_id;

        // attempt to load the data
        $this->load();
    }

    /**
     * Loads customer information into the object.
     *
     * @throws CustomerNotFoundException
     */
    abstract protected function load();

    /**
     * Returns an array of contracts for this customer.
     *
     * @param   mixed $options options An array of options that determine which contracts should be returned
     * @return  Contract[] An array of Contract objects
     */
    abstract public function getContracts($options = []);

    /**
     * Returns an array of contact objects for this customer.
     *
     * @return  Contact[] An array of Contact objects
     */
    abstract public function getContacts();

    /**
     * Returns various settings used when creating an issue
     *
     * @return array
     */
    abstract public function getSettings();

    /**
     * Returns an array of details about this customer
     *
     * @return  array
     */
    abstract public function getDetails();

    /**
     * Returns a message to be displayed to a customer on the top of the issue creation page.
     *
     * @return string
     */
    abstract public function getNewIssueMessage();

    //
//    /**
//     * Method used to get the overall statistics of issues in the system for a
//     * given customer.
//     *
//     * @param   mixed $contract_ids
//     * @return  array The customer related issue statistics
//     */
//    abstract public function getOverallStats($contract_ids);

    public function getCustomerID()
    {
        return $this->customer_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAccountManager()
    {
        // TODO: Figure out what this should return. Name, email, user ID, etc?
        return $this->account_manager;
    }

    /**
     * String representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return 'ID: ' . $this->customer_id . "\n" .
            'Name: ' . $this->name . "\n";
    }

    /**
     * Returns any notes for for the specified customer.
     *
     * @return  array an array containing the note details
     */
    public function getNoteDetails()
    {
        $stmt = 'SELECT
                    cno_id,
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    `customer_note`
                WHERE
                    cno_customer_id = ?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$this->customer_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the list of technical account managers for
     * a given customer ID.
     *
     * @return  array The list of account managers
     */
    public function getEventumAccountManagers()
    {
        $stmt = 'SELECT
                    cam_usr_id,
                    usr_email,
                    cam_type
                 FROM
                    `customer_account_manager`,
                    `user`
                 WHERE
                    cam_usr_id=usr_id AND
                    cam_prj_id=? AND
                    cam_customer_id=?';
        $params = [$this->crm->getProjectID(), $this->customer_id];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        return $res;
    }
}

class CustomerNotFoundException extends CRMException
{
    public function __construct($customer_id, Exception $previous = null)
    {
        parent::__construct("Customer '" . $customer_id . "' not found", 0, $previous);
    }
}
