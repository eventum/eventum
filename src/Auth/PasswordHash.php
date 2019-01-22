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

namespace Eventum\Auth;

use RuntimeException;

/**
 * Class dealing with user passwords
 */
class PasswordHash
{
    private const HASH_ALGO = PASSWORD_DEFAULT;

    /**
     * Hash the password
     *
     * @param string $password The password to hash
     * @throws RuntimeException
     * @return string the hashed password, throws on error
     */
    public static function hash(string $password): string
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
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return bool If the password matches the hash
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash The hash to test
     * @return bool true if the password needs to be rehashed
     */
    public static function needs_rehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::HASH_ALGO);
    }
}
