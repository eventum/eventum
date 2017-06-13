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

/**
 * Class dealing with user passwords
 */
class AuthPassword
{
    const HASH_ALGO = PASSWORD_DEFAULT;

    /**
     * Hash the password
     *
     * @param string $password The password to hash
     * @throws RuntimeException
     * @return string the hashed password, throws on error
     */
    public static function hash($password)
    {
        $res = password_hash($password, self::HASH_ALGO);
        if (!$res) {
            throw new RuntimeException('password hashing failed');
        }

        return $res;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $hash The hash to verify against
     * @param string $password The password to verify
     * @throws InvalidArgumentException in case non-strings were passed as hash or password
     * @return bool If the password matches the hash
     */
    public static function verify($password, $hash)
    {
        if (!is_string($password) || !is_string($hash)) {
            throw new InvalidArgumentException('password and hash need to be strings');
        }

        // verify passwords in constant time, i.e always do all checks
        $cmp = 0;

        $cmp |= (int) password_verify($password, $hash);

        // legacy authentication methods
        $cmp |= (int) self::cmp($hash, base64_encode(pack('H*', md5($password))));
        $cmp |= (int) self::cmp($hash, md5($password));

        return (bool) $cmp;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash The hash to test
     * @return bool true if the password needs to be rehashed
     */
    public static function needs_rehash($hash)
    {
        return password_needs_rehash($hash, self::HASH_ALGO);
    }

    /**
     * Compare strings using a timing attack resistant approach
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private static function cmp($str1, $str2)
    {
        if (Misc::countBytes($str1) != Misc::countBytes($str2) || Misc::countBytes($str1) <= 13) {
            return false;
        }

        $status = 0;
        $length = Misc::countBytes($str1);
        for ($i = 0; $i < $length; $i++) {
            $status |= (ord($str1[$i]) ^ ord($str2[$i]));
        }

        return $status === 0;
    }
}
