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

use Eventum\Config\Paths;
use Eventum\Db\DatabaseException;
use Eventum\Extension\ExtensionLoader;
use Eventum\ServiceContainer;

/**
 * Handles the interactions between Eventum and partner backends.
 */
class Partner
{
    /**
     * Return the appropriate partner backend class associated with the
     * given $par_code.
     *
     * @internal
     * @param   string $par_code The partner code
     * @return  Abstract_Partner_Backend
     * @deprecated will be removed in 3.3.0
     */
    public static function getBackend($par_code)
    {
        static $setup_backends;

        if (!isset($setup_backends[$par_code])) {
            $instance = static::getExtensionLoader()->createInstance($par_code);
            $setup_backends[$par_code] = $instance;
        }

        return $setup_backends[$par_code];
    }

    /**
     * @static
     * @param $iss_id
     * @return Abstract_Partner_Backend[]
     */
    private static function getBackendsByIssue($iss_id)
    {
        $partners = self::getPartnerCodesByIssue($iss_id);
        $backends = [];
        foreach ($partners as $par_code) {
            $backends[] = self::getBackend($par_code);
        }

        return $backends;
    }

    /**
     * @param int $prj_id
     */
    public static function getPartnersByProject($prj_id)
    {
        $sql = 'SELECT
                    pap_par_code
                FROM
                    `partner_project`
                WHERE
                    pap_prj_id = ?';

        try {
            $res = DB_Helper::getInstance()->getColumn($sql, [$prj_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        $return = [];
        foreach ($res as $partner) {
            $return[$partner] = [
                'name' => self::getName($partner),
            ];
        }

        return $return;
    }

    public static function getPartnerCodesByIssue($iss_id)
    {
        $prj_id = Issue::getProjectID($iss_id);
        $sql = 'SELECT
                    ipa_par_code
                FROM
                    `issue_partner`,
                    `partner_project`
                WHERE
                    ipa_par_code = pap_par_code AND
                    pap_prj_id = ? AND
                    ipa_iss_id = ?';
        try {
            $partners = DB_Helper::getInstance()->getColumn($sql, [$prj_id, $iss_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $partners;
    }

    public static function getPartnersByIssue($iss_id)
    {
        $partners = self::getPartnerCodesByIssue($iss_id);

        $return = [];
        foreach ($partners as $par_code) {
            $return[$par_code] = [
                'name' => self::getName($par_code),
                'message' => self::getIssueMessage($par_code, $iss_id),
            ];
        }

        return $return;
    }

    /**
     * @param int $iss_id
     */
    public static function isPartnerEnabledForIssue($par_code, $iss_id)
    {
        if (in_array($par_code, self::getPartnerCodesByIssue($iss_id))) {
            return true;
        }

        return false;
    }

    public static function getName($par_code)
    {
        $backend = self::getBackend($par_code);

        return $backend->getName();
    }

    public static function getIssueMessage($par_code, $iss_id)
    {
        $backend = self::getBackend($par_code);

        return $backend->getIssueMessage($iss_id);
    }

    /**
     * @param int $iss_id
     */
    public static function handleNewEmail($iss_id, $sup_id): void
    {
        foreach (self::getBackendsByIssue($iss_id) as $backend) {
            $backend->handleNewEmail($iss_id, $sup_id);
        }
    }

    /**
     * @param int $iss_id
     * @param int $not_id
     */
    public static function handleNewNote($iss_id, $not_id): void
    {
        foreach (self::getBackendsByIssue($iss_id) as $backend) {
            $backend->handleNewNote($iss_id, $not_id);
        }
    }

    /**
     * @param int $iss_id
     * @param int $usr_id
     */
    public static function handleIssueChange($iss_id, $usr_id, $old_details, $changes): void
    {
        foreach (self::getBackendsByIssue($iss_id) as $backend) {
            $backend->handleIssueChange($iss_id, $usr_id, $old_details, $changes);
        }
    }

    /**
     * @static
     * @param $usr_id
     * @param string $feature create_issue, associate_emails, reports, export
     * @return bool|null
     */
    public static function canUserAccessFeature($usr_id, $feature)
    {
        $usr_details = User::getDetails($usr_id);
        if (!empty($usr_details['usr_par_code'])) {
            $backend = self::getBackend($usr_details['usr_par_code']);

            return $backend->canUserAccessFeature($usr_id, $feature);
        }

        return null;
    }

    /**
     * @param $usr_id
     * @param string $section partners, drafts, files, time, notes, phone, history, notification_list, authorized_repliers
     * @return bool|null
     */
    public static function canUserAccessIssueSection($usr_id, $section)
    {
        $usr_details = User::getDetails($usr_id);
        if (!empty($usr_details['usr_par_code'])) {
            $backend = self::getBackend($usr_details['usr_par_code']);

            return $backend->canUserAccessIssueSection($usr_id, $section);
        }

        return null;
    }

    /**
     * If the partner can edit the issue.
     *
     * @param int   $issue_id
     * @param int   $usr_id
     * @return bool|null
     */
    public static function canUpdateIssue($issue_id, $usr_id)
    {
        $usr_details = User::getDetails($usr_id);
        if (!empty($usr_details['usr_par_code'])) {
            $backend = self::getBackend($usr_details['usr_par_code']);

            return $backend->canUpdateIssue($issue_id, $usr_id);
        }

        return null;
    }

    /**
     * @internal
     */
    public static function getExtensionLoader(): ExtensionLoader
    {
        $localPath = ServiceContainer::getConfig()['local_path'];

        $dirs = [
            Paths::APP_INC_PATH . '/partner',
            $localPath . '/partner',
        ];

        return new ExtensionLoader($dirs, '%s_Partner_Backend');
    }
}
