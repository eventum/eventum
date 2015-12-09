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

require_once __DIR__ . '/../init.php';

if (!empty($_GET['custom_id'])) {
    $filters = Filter::getListing(true);
    foreach ($filters as $filter) {
        if ($filter['cst_id'] == (int) $_GET['custom_id']) {
            parse_str($filter['url'], $params);
            $params = array_merge($params, $_POST, $_GET);
            unset($params['custom_id']);
            $url = 'list.php?cat=search&' . http_build_query($params);
            Auth::redirect($url);
        }
    }
}
