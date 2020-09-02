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
use Eventum\Event;
use Eventum\EventDispatcher\EventManager;
use Eventum\Mail\MailBuilder;
use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class to handle the business logic related to the administration
 * of users and permissions in the system.
 */
class User
{
    public const ROLE_VIEWER = 1;
    public const ROLE_REPORTER = 2;
    public const ROLE_CUSTOMER = 3;
    public const ROLE_USER = 4;
    public const ROLE_DEVELOPER = 5;
    public const ROLE_MANAGER = 6;
    public const ROLE_ADMINISTRATOR = 7;
    public const ROLE_NEVER_DISPLAY = 9;

    // definition of roles
    private static $roles = [
        self::ROLE_VIEWER => 'Viewer',
        self::ROLE_REPORTER => 'Reporter',
        self::ROLE_CUSTOMER => 'Customer',
        self::ROLE_USER => 'Standard User',
        self::ROLE_DEVELOPER => 'Developer',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_ADMINISTRATOR => 'Administrator',
    ];

    private static $localized_roles;

    private static function getLocalizedRoles()
    {
        if (self::$localized_roles === null) {
            foreach (self::$roles as $id => $role) {
                self::$localized_roles[$id] = ev_gettext($role);
            }
        }

        return self::$localized_roles;
    }

    /**
     * Method to reset localized roles, i.e after changing active language.
     */
    public static function resetLocalizedRoles(): void
    {
        self::$localized_roles = null;
    }

    /**
     * Method used to get the user ID associated with the given customer
     * contact ID.
     *
     * @param   int $customer_contact_id The customer contact ID
     * @return  int The user ID
     * @deprecated use UserRepository
     */
    public static function getUserIDByContactID($customer_contact_id)
    {
        $stmt = 'SELECT
                    usr_id
                 FROM
                    `user`
                 WHERE
                    usr_customer_contact_id=?';

        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$customer_contact_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the account email address associated with the given
     * customer contact ID.
     *
     * @param   int $customer_contact_id The customer contact ID
     * @return  string The user's email address
     * @deprecated use UserRepository
     */
    public static function getEmailByContactID($customer_contact_id)
    {
        $stmt = 'SELECT
                    usr_email
                 FROM
                    `user`
                 WHERE
                    usr_customer_contact_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$customer_contact_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the SMS email address associated with the given
     * user ID.
     *
     * @param   int $usr_id The user ID
     * @return  string The user's SMS email address
     */
    public static function getSMS($usr_id)
    {
        $stmt = 'SELECT
                    usr_sms_email
                 FROM
                    `user`
                 WHERE
                    usr_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to update the SMS email address associated with the given
     * user ID.
     *
     * @param   int $usr_id The user ID
     * @param   string $sms_email The user's SMS email address
     * @return  bool Whether the update was successful or not
     */
    public static function updateSMS($usr_id, $sms_email)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_sms_email=?
                 WHERE
                    usr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$sms_email, $usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the customer contact ID associated with
     * the given user ID.
     *
     * @param   int $usr_id The user ID
     * @return  int The customer contact ID
     */
    public static function getCustomerContactID($usr_id)
    {
        $stmt = 'SELECT
                    usr_customer_contact_id
                 FROM
                    `user`
                 WHERE
                    usr_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method used to get the customer ID associated with
     * the given user ID.
     *
     * @param   int $usr_id The user ID
     * @return  int The customer ID
     */
    public static function getCustomerID($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = 'SELECT
                    usr_customer_id
                 FROM
                    `user`
                 WHERE
                    usr_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $returns[$usr_id] = $res;

        return $res;
    }

    /**
     * Method used to update the user account and set the user as a confirmed one.
     *
     * @param   string $email The email address
     * @return  bool
     */
    public static function confirmVisitorAccount($email)
    {
        $stmt = "UPDATE
                    `user`
                 SET
                    usr_status='active'
                 WHERE
                    usr_email=?";
        try {
            DB_Helper::getInstance()->query($stmt, [$email]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to check whether the hash passed in the confirmation URL is
     * a valid one when comparing against the provided email address.
     *
     * @param   string $email The email address associated with the user account
     * @param   string $hash The md5 hash string to be checked against
     * @return  int -1 if there was an error in the query, -2 for users that don't exist,
     *                  -3 if it cannot be authenticated and 1 if it did work
     */
    public static function checkHash($email, $hash)
    {
        $stmt = 'SELECT
                    usr_full_name
                 FROM
                    `user`
                 WHERE
                    usr_email=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$email]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($res == null) {
            return -2;
        }
        $check_hash = md5($res . $email . Auth::privateKey());
        if ($hash != $check_hash) {
            return -3;
        }

        return 1;
    }

    /**
     * Method used to create a new user account with pending status and send a
     * confirmation email to the prospective user.
     *
     * @param   string $role The user role
     * @param   array $projects The list of projects that this user will be associated with
     * @return  int 1 if the creation worked, -1 otherwise
     */
    public static function createVisitorAccount($role, $projects)
    {
        $usr_email = $_POST['email'];
        $full_name = $_POST['full_name'];
        $passwd = $_POST['passwd'];

        // check for double submits
        if (Auth::userExists($usr_email)) {
            return -2;
        }

        $stmt = 'INSERT INTO
                    `user`
                 (
                    usr_created_date,
                    usr_full_name,
                    usr_email,
                    usr_status
                 ) VALUES (?, ?, ?, ?)';

        try {
            DB_Helper::getInstance()->query(
                $stmt, [
                    Date_Helper::getCurrentDateGMT(),
                    $full_name,
                    $usr_email,
                    'pending',
                ]
            );
        } catch (DatabaseException $e) {
            return -1;
        }

        $usr_id = DB_Helper::get_last_insert_id();

        try {
            self::updatePassword($usr_id, $passwd);
        } catch (Exception $e) {
            ServiceContainer::getLogger()->error($e);

            return -1;
        }

        // add the project associations!
        foreach ($projects as $prj_id) {
            Project::associateUser($prj_id, $usr_id, $role);
        }

        // send confirmation email to user
        $hash = md5($full_name . $usr_email . Auth::privateKey());

        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/visitor_account.tpl.text');
        $tpl->assign([
            'app_title' => Setup::getToolCaption(),
            'email' => $usr_email,
            'hash' => $hash,
        ]);
        $text_message = $tpl->getTemplateContents();

        // TRANSLATORS: %1 - Setup::getShortName()
        $subject = ev_gettext('%s: New Account - Confirmation Required', Setup::getShortName());

        $builder = new MailBuilder();
        $builder->addTextPart($text_message)
            ->getMessage()
            ->setSubject($subject)
            ->setTo($usr_email);

        Mail_Queue::queue($builder, $usr_email);

        return 1;
    }

    /**
     * Method used to send a confirmation email to the user that is associated
     * to the email address.
     *
     * @param   string $usr_id The user ID
     */
    public static function sendPasswordConfirmationEmail($usr_id): void
    {
        $info = self::getDetails($usr_id);
        // send confirmation email to user
        $usr_email = $info['usr_email'];
        $hash = md5($info['usr_full_name'] . $usr_email . Auth::privateKey());

        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/password_confirmation.tpl.text');
        $tpl->assign([
            'app_title' => Setup::getToolCaption(),
            'user' => $info,
            'hash' => $hash,
        ]);
        $text_message = $tpl->getTemplateContents();

        // TRANSLATORS: %s - Setup::getShortName()
        $subject = ev_gettext('%s: New Password - Confirmation Required', Setup::getShortName());

        $builder = new MailBuilder();
        $builder->addTextPart($text_message)
            ->getMessage()
            ->setSubject($subject)
            ->setTo($usr_email);

        Mail_Queue::queue($builder, $usr_email);
    }

    /**
     * Method used to confirm the request of a new password and send an email
     * to the user with the new random password.
     *
     * @param   string $email The email address
     */
    public static function confirmNewPassword($email): void
    {
        $usr_id = self::getUserIDByEmail($email);
        // create the new password
        $password = substr(md5(microtime() . uniqid('')), 0, 12);
        self::updatePassword($usr_id, $password, true);
    }

    public static function getUserIDByExternalID(string $external_id): ?int
    {
        $sql = 'SELECT
                    usr_id
                FROM
                    `user`
                WHERE
                    usr_external_id=?';

        return DB_Helper::getInstance()->getOne($sql, [$external_id]);
    }

    /**
     * Method used to lookup the user ID of a given email address.
     *
     * @param   string $email The email address associated with the user account
     * @param   bool $check_aliases if user aliases should be checked as well
     * @return  int The user ID
     */
    public static function getUserIDByEmail($email, $check_aliases = false)
    {
        static $returns;

        if (!is_string($email)) {
            ServiceContainer::getLogger()->error('$email parameter is not a string', ['type' => gettype($email), 'value' => $email]);

            return null;
        }

        if (!empty($returns[$email])) {
            return $returns[$email];
        }

        $stmt = 'SELECT
                    usr_id
                 FROM
                    `user`
                 WHERE
                    usr_email=?';
        $res = DB_Helper::getInstance()->getOne($stmt, [$email]);

        if (empty($res) && $check_aliases) {
            $res = self::getUserIDByAlias($email);
        }
        $returns[$email] = $res;

        return $returns[$email];
    }

    /**
     * Method used to check whether an user is set to status active
     * or not.
     *
     * @param   string $status The status of the user
     * @return  bool
     */
    public static function isActiveStatus($status)
    {
        if ($status == 'active') {
            return true;
        }

        return false;
    }

    /**
     * Method used to check whether an user is set to status pending
     * or not.
     *
     * @param   string $status The status of the user
     * @return  bool
     */
    public static function isPendingStatus($status)
    {
        if ($status == 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Method used to get the list of all active users available in the system
     * as an associative array of user IDs => user full names.
     *
     * @param   int $prj_id The id of the project to show users from
     * @param   int $role The role ID of the user
     * @return  array The associative array of users
     */
    public static function getActiveAssocList($prj_id = null, $role = null)
    {
        $stmt = 'SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    `user`';
        $params = [];

        if ($prj_id) {
            $stmt .= ',
                    `project_user`';
        }
        $stmt .= "
                 WHERE
                    usr_status='active' AND
                    usr_id != ?";
        $system_user_id = Setup::getSystemUserId();
        $params[] = $system_user_id;

        if ($prj_id) {
            $stmt .= ' AND pru_prj_id = ? AND
                       usr_id = pru_usr_id';
            $params[] = $prj_id;
            if ($role) {
                $stmt .= ' AND pru_role > ?';
                $params[] = $role;
            }
        }
        $stmt .= '
                 ORDER BY
                    usr_full_name ASC';

        return DB_Helper::getInstance()->getPair($stmt, $params);
    }

    /**
     * Method used to get an associative array of the available roles.
     *
     * @return  array The list of roles
     */
    public static function getAssocRoleIDs()
    {
        $assoc_roles = [];
        foreach (self::$roles as $key => $value) {
            $value = str_replace(' ', '_', strtolower($value));
            $assoc_roles[$value] = (integer) $key;
        }

        return $assoc_roles;
    }

    /**
     * Method used to get the full list of roles available in the
     * system.
     *
     * @param   array $exclude_role The list of roles to ignore
     * @return  array The list of roles
     */
    public static function getRoles($exclude_role = null)
    {
        if (empty($exclude_role)) {
            return self::getLocalizedRoles();
        }

        $roles = self::getLocalizedRoles();
        if (!is_array($exclude_role)) {
            $exclude_role = [$exclude_role];
        }

        foreach ($exclude_role as $role_id) {
            unset($roles[$role_id]);
        }

        return $roles;
    }

    /**
     * Method used to get the role title for a specific role ID.
     *
     * @param   int $role_id The role ID
     * @return  string The role title
     */
    public static function getRole($role_id)
    {
        $roles = self::getLocalizedRoles();
        // XXX manage/custom_fields.php uses role_id = 9 as "Never Display", which is hack
        return $roles[$role_id] ?? null;
    }

    /**
     * Method used to get the role for a specific user and project.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @return  int The role ID
     */
    public static function getRoleByUser($usr_id, $prj_id)
    {
        static $returns;

        if ($usr_id == Setup::getSystemUserId()) {
            return self::ROLE_ADMINISTRATOR;
        }

        if (!empty($returns[$usr_id][$prj_id])) {
            return $returns[$usr_id][$prj_id];
        }

        $stmt = 'SELECT
                    pru_role
                 FROM
                    `project_user`
                 WHERE
                    pru_usr_id=? AND
                    pru_prj_id=?';

        $res = (int)DB_Helper::getInstance()->getOne($stmt, [$usr_id, $prj_id]);

        return $returns[$usr_id][$prj_id] = $res;
    }

    /**
     * Method used to get the account details of a specific user.
     *
     * @param   int $usr_id The user ID
     * @return  array The account details
     */
    public static function getDetails($usr_id)
    {
        $res = self::getDetailsAssoc([$usr_id]);

        return reset($res);
    }

    /**
     * Method used to get the account details of a list users.
     * Options:
     * - groups Whether to load groups information for users, default true
     *
     * @param array $usr_ids The user ID
     * @param array $options
     * @return array List of accounts with account details
     */
    public static function getDetailsAssoc($usr_ids, $options = [])
    {
        static $returns;
        $key = md5(serialize([$usr_ids, $options]));

        if (empty($returns[$key])) {
            $itemlist = DB_Helper::buildList($usr_ids);

            $stmt = "SELECT
                        usr.*,
                        GROUP_CONCAT(ual_email) ual_email
                     FROM
                        `user` usr
                     LEFT JOIN `user_alias` ual ON ual.ual_usr_id=usr.usr_id
                     WHERE
                        usr_id IN ($itemlist)
                     GROUP BY usr_id
                     ";

            $res = DB_Helper::getInstance()->getAll($stmt, $usr_ids);

            $load_groups = $options['groups'] ?? true;

            foreach ($res as &$row) {
                // TODO: unset usr_password, load only when needed
                //unset($row['usr_password']);

                if ($load_groups) {
                    $row['groups'] = self::getGroups($row['usr_id']);
                    $row['group_ids'] = array_keys($row['groups']);
                    $row['group_names'] = array_values($row['groups']);
                }

                $roles = Project::getAssocList($row['usr_id'], false, true);
                $row['projects'] = array_keys($roles);
                $row['roles'] = $roles;
                $row['aliases'] = explode(',', $row['ual_email']);
            }
            unset($row);

            $returns[$key] = $res;
        }

        return $returns[$key];
    }

    /**
     * Method used to get the full name of the specified user.
     *
     * @param   int|array $usr_id The user ID
     * @return  string The user' full name
     */
    public static function getFullName($usr_id)
    {
        static $returns;

        if (!is_array($usr_id)) {
            $items = [$usr_id];
        } else {
            $items = $usr_id;
        }

        $key = md5(serialize($usr_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        if (count($items) < 1) {
            if (!is_array($usr_id)) {
                return '';
            }

            return [];
        }

        $itemlist = DB_Helper::buildList($items);

        $stmt = "SELECT
                    usr_full_name
                 FROM
                    `user`
                 WHERE
                    usr_id IN ($itemlist)";
        try {
            if (!is_array($usr_id)) {
                $res = DB_Helper::getInstance()->getOne($stmt, $items);
            } else {
                $res = DB_Helper::getInstance()->getColumn($stmt, $items);
            }
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$key] = $res;

        return $res;
    }

    /**
     * Method used to get the email address of the specified user.
     *
     * TODO: fix api to be stable, always strings or always arrays in/out
     *
     * @param   int|int[] $usr_id The user ID or user ids
     * @return  string|string[] The user' full name
     */
    public static function getEmail($usr_id)
    {
        static $returns;

        if (!is_array($usr_id)) {
            $items = [$usr_id];
        } else {
            $items = $usr_id;
        }

        $key = md5(serialize($usr_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        if (count($items) < 1) {
            if (!is_array($usr_id)) {
                return '';
            }

            return [];
        }

        $itemlist = DB_Helper::buildList($items);

        $stmt = "SELECT
                    usr_email
                 FROM
                    `user`
                 WHERE
                    usr_id IN ($itemlist)";

        if (!is_array($usr_id)) {
            $res = DB_Helper::getInstance()->getOne($stmt, $items);
        } else {
            $res = DB_Helper::getInstance()->getColumn($stmt, $items);
        }

        $returns[$key] = $res;

        return $res;
    }

    /**
     * Method used to get the group ids and titles for the specified user.
     *
     * @param   int $usr_id The user ID
     * @return  string[] The user's full name
     */
    public static function getGroups($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $sql = 'SELECT
                  ugr_grp_id,
                  grp_name
                FROM
                    `user_group`,
                    `group`
                WHERE
                    ugr_grp_id = grp_id AND
                    ugr_usr_id = ?';
        $res = DB_Helper::getInstance()->getPair($sql, [$usr_id]);

        $returns[$usr_id] = $res;

        return $res;
    }

    /**
     * Returns the group ids of the specified user
     *
     * @param   int $usr_id The user ID
     * @return  int[] An array of group ids
     */
    public static function getGroupIDs($usr_id)
    {
        return array_keys(self::getGroups($usr_id));
    }

    /**
     * Returns the status of the user associated with the given email address.
     *
     * @param   string $email The email address
     * @return  string The user status
     */
    public static function getStatusByEmail($email)
    {
        static $returns;

        if (isset($returns[$email])) {
            return $returns[$email];
        }

        $email = self::getEmail(self::getUserIDByEmail($email, true));

        $stmt = 'SELECT
                    usr_status
                 FROM
                    `user`
                 WHERE
                    usr_email=?';

        $res = DB_Helper::getInstance()->getOne($stmt, [$email]);

        $returns[$email] = $res;

        return $res;
    }

    const USER_STATUS_ACTIVE = 'active';
    const USER_STATUS_INACTIVE = 'inactive';

    /**
     * Method used to change the status of users, making them inactive
     * or active.
     *
     * @param int[]|int $usr_ids
     * @param string $status
     * @return bool
     */
    public static function changeStatus($usr_ids, $status)
    {
        // check if the user being inactivated is the last one
        if ($status == self::USER_STATUS_INACTIVE) {
            $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    `user`
                 WHERE
                    usr_status=?';

            $total_active = DB_Helper::getInstance()->getOne($stmt, [self::USER_STATUS_ACTIVE]);
            if ($total_active < 2) {
                return false;
            }
        }

        $usr_ids = (array) $usr_ids;
        $items = DB_Helper::buildList($usr_ids);
        $stmt = "UPDATE
                    `user`
                 SET
                    usr_status=?
                 WHERE
                    usr_id IN ($items)";
        $params = array_merge([$status], $usr_ids);
        DB_Helper::getInstance()->query($stmt, $params);

        return true;
    }

    /**
     * Method used to update the account full name for a specific user.
     *
     * @param   int $usr_id The user ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function updateFullName($usr_id)
    {
        $full_name = trim(strip_tags($_POST['full_name']));
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_full_name=?
                 WHERE
                    usr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$full_name, $usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        Notification::notifyUserAccount($usr_id);

        return 1;
    }

    /**
     * Method used to update the account email for a specific user.
     *
     * @param   int $usr_id The user ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function updateEmail($usr_id)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_email=?
                 WHERE
                    usr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['email'], $usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        Notification::notifyUserAccount($usr_id);

        return 1;
    }

    /**
     * Method to set the user password.
     * It calls out auth backend, which will store the password hash.
     *
     * @param int $usr_id The user ID
     * @param string $password Plain text user password
     * @param bool $send_notification Whether to send the notification email or not
     * @throw InvalidArgumentException|BadMethodCallException in case password was not set
     */
    public static function updatePassword($usr_id, $password, $send_notification = false): void
    {
        // reject setting empty password
        if ($password === '') {
            throw new InvalidArgumentException("Can't set empty password");
        }

        $res = Auth::getAuthBackend()->updatePassword($usr_id, $password);
        if (!$res) {
            throw new BadMethodCallException('Password set rejected by auth backend');
        }

        if ($send_notification) {
            Notification::notifyUserPassword($usr_id, $password);
        }
    }

    /**
     * Method used to update the account details for a specific user.
     *
     * @param int $usr_id
     * @param array $user The array of user information
     * @param bool $notify
     * @return bool
     */
    public static function update($usr_id, array $user, $notify = true)
    {
        // system account should not be updateable
        if ($usr_id == Setup::getSystemUserId()) {
            return false;
        }

        $params = [
            'usr_email' => $user['email'],
        ];

        if (isset($user['full_name'])) {
            $params['usr_full_name'] = $user['full_name'];
        }

        if (isset($user['external_id'])) {
            $params['usr_external_id'] = $user['external_id'];
        }

        if (isset($user['par_code'])) {
            $params['usr_par_code'] = $user['par_code'];
        }

        $stmt = 'UPDATE
                    `user`
                 SET ' . DB_Helper::buildSet($params) . ' WHERE usr_id=?';
        $params[] = $usr_id;

        DB_Helper::getInstance()->query($stmt, $params);

        if (!empty($user['password'])) {
            self::updatePassword($usr_id, $user['password']);
        }

        if (isset($user['role'])) {
            // update the project associations now
            $stmt = 'DELETE FROM
                        `project_user`
                     WHERE
                        pru_usr_id=?';
            DB_Helper::getInstance()->query($stmt, [$usr_id]);

            foreach ($user['role'] as $prj_id => $role) {
                if ($role < 1) {
                    continue;
                }
                $stmt = 'INSERT INTO
                            `project_user`
                         (
                            pru_prj_id,
                            pru_usr_id,
                            pru_role
                         ) VALUES (
                            ?, ?, ?
                         )';
                $params = [$prj_id, $usr_id, $role];
                DB_Helper::getInstance()->query($stmt, $params);
            }
        }

        if (isset($user['groups'])) {
            $stmt = 'DELETE FROM
                        `user_group`
                     WHERE
                        ugr_usr_id=?';
            DB_Helper::getInstance()->query($stmt, [$usr_id]);

            foreach ($user['groups'] as $grp_id) {
                Group::addUser($usr_id, $grp_id);
            }
        }

        if ($notify == true) {
            if (!empty($user['password'])) {
                Notification::notifyUserPassword($usr_id, $user['password']);
            } else {
                Notification::notifyUserAccount($usr_id);
            }
        }

        // add user id and do not expose password to event
        $user['id'] = (int)$usr_id;
        unset($user['password']);

        $event = new GenericEvent(null, $user);
        EventManager::dispatch(Event\SystemEvents::USER_UPDATE, $event);

        return true;
    }

    /**
     * Method used to add a new user to the system.
     *
     * @param   array $user The array of user information
     * @return  int usr_id being created
     */
    public static function insert(array $user)
    {
        $params = [
            $user['customer_id'] ?? null,
            $user['contact_id'] ?? null,
            Date_Helper::getCurrentDateGMT(),
            $user['full_name'],
            $user['email'],
            $user['external_id'] ?? null,
            $user['par_code'] ?? null,
        ];
        $stmt = 'INSERT INTO
                    `user`
                 (
                    usr_customer_id,
                    usr_customer_contact_id,
                    usr_created_date,
                    usr_full_name,
                    usr_email,
                    usr_external_id,
                    usr_par_code
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                 )';

        DB_Helper::getInstance()->query($stmt, $params);

        $usr_id = DB_Helper::get_last_insert_id();

        if (isset($user['password']) && $user['password'] !== '') {
            self::updatePassword($usr_id, $user['password']);
        }

        // add the project associations!
        $projects = [];
        foreach ($user['role'] as $prj_id => $role) {
            if ($role < 1) {
                continue;
            }
            Project::associateUser($prj_id, $usr_id, $role);
            $projects[] = $prj_id;
        }

        if (isset($user['groups'])) {
            foreach ($user['groups'] as $grp_id) {
                Group::addUser($usr_id, $grp_id);
            }
        }

        // send email to user
        Notification::notifyNewUser($usr_id, $user['password']);

        // add user id and do not expose password to event
        $user['id'] = (int)$usr_id;
        unset($user['password']);

        $event = new GenericEvent(null, $user);
        EventManager::dispatch(Event\SystemEvents::USER_CREATE, $event);

        return $usr_id;
    }

    /**
     * Method used to get the list of users available in the system.
     *
     * Options:
     * - customers Whether to include customers in list, default true, i.e do not filter out
     * - inactive Whether to include inactive users in list, default false
     * - groups Whether to load groups information for users
     *
     * @param array $options
     * @return  array The list of users
     */
    public static function getList(array $options = []): array
    {
        $system_user_id = Setup::getSystemUserId();
        // FIXME: what about other statuses like "pending"?
        $stmt = 'SELECT
                    usr_id
                 FROM
                    `user`
                 WHERE
                    usr_id != ?';
        $params = [$system_user_id];

        $show_inactive = $options['inactive'] ?? false;
        $show_customers = $options['customers'] ?? true;

        if (!$show_inactive) {
            $stmt .= ' AND usr_status != ?';
            $params[] = 'inactive';
        }
        $stmt .= '
                ORDER BY
                    usr_status ASC,
                    usr_full_name ASC';

        $usr_ids = DB_Helper::getInstance()->getColumn($stmt, $params);

        $prj_id = Auth::getCurrentProject();
        $data = [];

        $get_partner_name = static function ($name) {
            static $cache;
            if (!isset($cache[$name])) {
                $cache[$name] = Partner::getName($name);
            }

            return $cache[$name];
        };

        $res = self::getDetailsAssoc($usr_ids, $options);
        foreach ($res as $row) {
            // handle show_customers = false
            if (!$show_customers) {
                $roles = $row['roles'];
                if (count($roles) === 1) {
                    $role = current($roles);
                    if ($role['pru_role'] == self::ROLE_CUSTOMER) {
                        continue;
                    }
                }

                $pru_role = $roles[$prj_id]['pru_role'] ?? 0;
                if ($pru_role == self::ROLE_CUSTOMER) {
                    continue;
                }
            }

            if (!empty($row['usr_par_code'])) {
                $row['partner_name'] = $get_partner_name($row['usr_par_code']);
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Method used to get an associative array of the user's email address and
     * user ID.
     *
     * @return  array The list of users
     */
    public static function getAssocEmailList()
    {
        static $emails;

        if (!empty($emails)) {
            return $emails;
        }

        $stmt = 'SELECT
                    LOWER(usr_email),
                    usr_id
                 FROM
                    `user`';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        $emails = $res;

        return $res;
    }

    /**
     * Method used to get an associative array of the user ID and
     * full name of the users available in the system.
     *
     * @return  array The list of users
     */
    public static function getAssocList()
    {
        $stmt = 'SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    `user`
                 ORDER BY
                    usr_full_name ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the full name and email for the specified
     * user.
     *
     * @param   int $usr_id The user ID
     * @return  array The email and full name
     */
    public static function getNameEmail($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = 'SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    `user`
                 WHERE
                    usr_id=?';
        $res = DB_Helper::getInstance()->getRow($stmt, [$usr_id]);

        $returns[$usr_id] = $res;

        return $res;
    }

    /**
     * Method used to get the appropriate 'From' header for a
     * specified user.
     *
     * @param   int $usr_id The user ID
     * @return  string The formatted 'From' header
     */
    public static function getFromHeader($usr_id)
    {
        $info = self::getNameEmail($usr_id);

        return $info['usr_full_name'] . ' <' . $info['usr_email'] . '>';
    }

    /**
     * Returns the list of all users who are currently marked as
     * clocked-in.
     *
     * @return  array The list of clocked-in users
     */
    public static function getClockedInList()
    {
        $stmt = 'SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    `user`
                 WHERE
                    usr_clocked_in=1';

        return DB_Helper::getInstance()->getPair($stmt);
    }

    /**
     * Marks a user as clocked in.
     *
     * @param   int $usr_id the id of the user to clock out
     * @return int
     */
    public static function clockIn($usr_id)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_clocked_in = 1
                 WHERE
                    usr_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Marks a user as clocked out.
     *
     * @param   int $usr_id the id of the user to clock out
     * @return int
     */
    public static function clockOut($usr_id)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_clocked_in = 0
                 WHERE
                    usr_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns true if a user is clocked in.
     *
     * @param   int $usr_id the id of the user to clock out
     * @return  bool True if the user is logged in, false otherwise
     */
    public static function isClockedIn($usr_id)
    {
        $setup = ServiceContainer::getConfig();
        // If clock in handling is disabled, say that we are always clocked in
        if ($setup['handle_clock_in'] === 'disabled') {
            return true;
        }
        $stmt = 'SELECT
                    usr_clocked_in
                 FROM
                    `user`
                 WHERE
                    usr_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($res == 1) {
            return true;
        }

        return false;
    }

    public static function getLang($usr_id, $force_refresh = false)
    {
        static $returns;

        if (empty($returns[$usr_id]) || $force_refresh == true) {
            $sql = 'SELECT
                        usr_lang
                    FROM
                        `user`
                    WHERE
                        usr_id = ?';
            $res = DB_Helper::getInstance()->getOne($sql, [$usr_id]);

            $returns[$usr_id] = $res ?: Setup::getDefaultLocale();
        }

        return $returns[$usr_id];
    }

    /**
     * @param int $usr_id
     * @param string $language
     */
    public static function setLang($usr_id, $language)
    {
        $sql = 'UPDATE
                    `user`
                SET
                    usr_lang = ?
                WHERE
                    usr_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$language, $usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param int $usr_id
     */
    public static function getAliases($usr_id)
    {
        $sql = 'SELECT
                    ual_email
                FROM
                    `user_alias`
                WHERE
                    ual_usr_id = ?';
        try {
            $res = DB_Helper::getInstance()->getColumn($sql, [$usr_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    public static function addAlias($usr_id, $email)
    {
        // see if alias belongs to a user right now
        // it may belong to some other account!
        // these checks will avoid adding email alias to two unrelated accounts
        $email_usr_id = self::getUserIDByEmail($email);
        if ($email_usr_id) {
            return false;
        }

        $existing_alias_usr_id = self::getUserIDByAlias($email);
        if ($existing_alias_usr_id) {
            return false;
        }

        $sql = 'INSERT INTO
                    `user_alias`
                SET
                    ual_usr_id = ?,
                    ual_email = ?';

        try {
            DB_Helper::getInstance()->query($sql, [$usr_id, $email]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param int $usr_id
     */
    public static function removeAlias($usr_id, $email)
    {
        $sql = 'DELETE FROM
                    `user_alias`
                WHERE
                    ual_usr_id = ? AND
                    ual_email = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$usr_id, $email]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    public static function getUserIDByAlias($email)
    {
        $sql = 'SELECT
                    ual_usr_id
                FROM
                    `user_alias`
                WHERE
                    ual_email = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$email]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    public static function isPartner($usr_id)
    {
        $sql = 'SELECT
                    usr_par_code
                FROM
                    `user`
                WHERE
                    usr_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return !empty($res);
    }

    /**
     * @param int $usr_id
     */
    public static function getPartnerID($usr_id)
    {
        $sql = 'SELECT
                    usr_par_code
                FROM
                    `user`
                WHERE
                    usr_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res;
    }

    /**
     * @param int $usr_id
     */
    public static function getExternalID($usr_id)
    {
        $sql = 'SELECT
                    usr_external_id
                FROM
                    `user`
                WHERE
                    usr_id = ?';

        return DB_Helper::getInstance()->getOne($sql, [$usr_id]);
    }

    public static function unlock($usr_id)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_failed_logins = 0
                 WHERE
                    usr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }
}
