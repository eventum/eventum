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

namespace Eventum\Model\Repository;

use DB_Helper;
use Eventum\Model\Entity;

/**
 * @method Entity\PartnerProject findById(int $prj_id)
 * @method Entity\PartnerProject findOneByCode(string $code)
 */
class PartnerProjectRepository extends BaseRepository
{
    use Traits\FindByIdTrait;

    public function setProjectAssociation(Entity\PartnerProject $pap, array $projects): void
    {
        $db = DB_Helper::getInstance();
        $par_code = $pap->getCode();

        // delete all first, then re-insert
        $sql = 'DELETE FROM
                    `partner_project`
                WHERE
                    pap_par_code = ?';
        $db->query($sql, [$par_code]);

        foreach ($projects as $prj_id) {
            $sql = 'INSERT INTO
                            `partner_project`
                        SET
                            pap_par_code = ?,
                            pap_prj_id = ?';
            $db->query($sql, [$par_code, $prj_id]);
        }
    }
}
