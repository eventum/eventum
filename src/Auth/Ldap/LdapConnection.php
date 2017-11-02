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

namespace Eventum\Auth\Ldap;

use AuthException;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Adapter\ExtLdap\Collection;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Zend\Config\Config;

class LdapConnection
{
    /** @var Adapter */
    private $ldap;

    /** @var Config */
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->ldap = new Adapter($this->getConnectionConfig());
    }

    /**
     * Try binding user with password, return true if any of them succeeded
     *
     * @param string $user
     * @param string $password
     * @throws AuthException
     * @return bool|ConnectionException[]
     */
    public function checkAuthentication($user, $password)
    {
        if ($password === '') {
            throw new AuthException('The presented password must not be empty.');
        }

        /** @var ConnectionException[] $errors */
        $errors = [];
        $connection = $this->ldap->getConnection();

        foreach ($this->getUserBindDNs($user) as $dn) {
            try {
                $connection->bind($dn, $password);

                return true;
            } catch (ConnectionException $e) {
                $errors[] = $e;
            }
        }

        return $errors;
    }

    /**
     * Retrieve information from LDAP
     *
     * @param string $uid login or email
     * @return UserEntry|null
     */
    public function findUser($uid)
    {
        $filter = $this->getUserFilter($uid);

        $entry = $this->searchOne($this->config['basedn'], $filter);
        if (!$entry) {
            return null;
        }

        $user = new UserEntry($entry, $this->config);
        if ($user->getUid() != $uid && array_search($uid, $user->getEmails()) === false) {
            throw new AuthException("Found wrong user: {$user->getDn()}. Is your user filter correct?");
        }

        return $user;
    }

    /**
     * @param string $dn
     * @return \Generator|UserEntry[]
     */
    public function listUsers($dn = null)
    {
        $filter = $this->getUserFilter(null);

        $result = $this->search($dn ?: $this->config['basedn'], $filter);
        foreach ($result as $entry) {
            yield new UserEntry($entry, $this->config);
        }
    }

    /**
     * Connect to LDAP using binddn credentials.
     */
    private function connect()
    {
        $this->ldap->getConnection()->bind($this->config['binddn'], $this->config['bindpw']);
    }

    /**
     * Search LDAP returning Entry list.
     *
     * @param string $dn
     * @param string $filter
     * @param array $options
     * @return CollectionInterface|Collection|Entry[]
     */
    private function search($dn, $filter, array $options = [])
    {
        $this->connect();
        $query = $this->ldap->createQuery($dn, $filter, $options);

        return $query->execute();
    }

    /**
     * Search LDAP returning Entry or null
     *
     * @param string $dn
     * @param string $filter
     * @param array $options
     * @throws AuthException
     * @return Entry|null
     */
    private function searchOne($dn, $filter, array $options = [])
    {
        $options['maxItems'] = 1;
        $entries = $this->search($dn, $filter, $options);
        $count = count($entries);

        if (!$count) {
            return null;
        }

        if ($count > 1) {
            throw new AuthException('More than one entry found');
        }

        return $entries[0];
    }

    /**
     * Get list of DN's to try to bind user
     *
     * @param string $uid
     * @return array
     */
    private function getUserBindDNs($uid)
    {
        $username = $this->ldap->escape($uid, '', LdapInterface::ESCAPE_DN);
        $userdn = str_replace('%UID%', $username, $this->config['userdn']);

        return explode('|', $userdn);
    }

    /**
     * Build filter to search user based on login or emails.
     *
     * @param string $username
     * @return string
     */
    private function getUserFilter($username)
    {
        if (strpos($username, '@') === false) {
            $uid_key = 'uid';
        } else {
            $uid_key = 'mail';
        }

        $filter = $this->config['user_filter'] ?: '({uid_key}={username})';

        if ($username !== null) {
            $username = $this->ldap->escape($username, '', LdapInterface::ESCAPE_FILTER);
        } else {
            $username = '*';
        }

        return str_replace(['{uid_key}', '{username}'], [$uid_key, $username], $filter);
    }

    /**
     * Get config for LDAP Connection
     *
     * @return array
     */
    private function getConnectionConfig()
    {
        return [
            'host' => $this->config['host'],
            'port' => $this->config['port'],
        ];
    }
}
