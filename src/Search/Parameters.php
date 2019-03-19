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

namespace Eventum\Search;

use Search_Profile;
use Symfony\Component\HttpFoundation\Request;

class Parameters
{
    /** @var Request */
    private $request;
    /** @var int */
    private $usr_id;
    /** @var int */
    private $prj_id;
    /** @var array */
    private $profile;

    public function __construct(Request $request, int $usr_id, int $prj_id)
    {
        $this->request = $request;
        $this->usr_id = $usr_id;
        $this->prj_id = $prj_id;
    }

    public function get(string $name, bool $request_only = false, array $valid_values = [])
    {
        $value = null;

        if ($this->request->query->has($name)) {
            $value = $this->request->query->get($name);
        } elseif ($this->request->request->has($name)) {
            $value = $this->request->request->get($name);
        } elseif ($request_only) {
            return null;
        }

        if ($value !== null) {
            if ($valid_values && !in_array($value, $valid_values, true)) {
                return null;
            }

            return $value;
        }

        return $this->getSearchProfile('issue')[$name] ?? null;
    }

    public function getSearchProfile(string $type = 'issue'): array
    {
        return
            $this->profile[$type] ??
            $this->profile[$type] = Search_Profile::getProfile($this->usr_id, $this->prj_id, $type);
    }
}
