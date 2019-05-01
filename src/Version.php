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

namespace Eventum;

final class Version
{
    private const UNPARSED_VERSION = 'No version set (parsed as 1.0.0)';

    /** @var string */
    public $reference;
    /** @var string */
    public $version;
    /** @var null|string */
    public $hash;
    /** @var null|string */
    public $branch;

    /**
     * Extract version components from various forms.
     *
     * a tag: '3.7.0@859ccf532731b653c5af71f4151f173bc8fd1d42'
     * a branch: 'dev-package-versions@859ccf532731b653c5af71f4151f173bc8fd1d42'
     * detached HEAD: 'dev-bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6@bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6'
     * error: 'No version set (parsed as 1.0.0)@'
     */
    public function __construct(string $versionString)
    {
        [$reference, $hash] = explode('@', $versionString, 2);
        if ($reference === self::UNPARSED_VERSION) {
            return;
        }

        $this->reference = $reference;
        $this->hash = $hash ?: null;

        $parts = explode('-', $this->reference, 2);

        if ($parts[0] === 'dev') {
            $branch = implode('-', array_splice($parts, 1));

            // skip detached head
            if ($branch !== $this->hash) {
                $this->branch = $branch;
            }
        } else {
            $this->version = $parts[0];
        }
    }
}
