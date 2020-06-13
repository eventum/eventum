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

use Auth;
use Date_Helper;
use DB_Helper;
use Doctrine\ORM\EntityRepository;
use Eventum\Model\Entity;
use History;
use Partner;
use User;

/**
 * @method Entity\IssuePartner findById(int $prj_id)
 */
class IssuePartnerRepository extends EntityRepository
{
    use Traits\FindByIdTrait;

    public function setIssueAssociation(int $iss_id, array $partners): void
    {
        $old_partners = Partner::getPartnersByIssue($iss_id);
        foreach ($partners as $par_code) {
            $this->addPartnerToIssue($iss_id, $par_code);
            unset($old_partners[$par_code]);
        }

        // remove any unselected partners
        foreach ($old_partners as $par_code => $partner) {
            $this->removePartnerFromIssue($iss_id, $par_code);
        }
    }

    private function addPartnerToIssue(int $issueId, string $par_code): void
    {
        $current_partners = Partner::getPartnerCodesByIssue($issueId);
        if (in_array($par_code, $current_partners, true)) {
            return;
        }

        $params = [$issueId, $par_code, Date_Helper::getCurrentDateGMT()];
        $sql = 'INSERT INTO
                    `issue_partner`
                SET
                    ipa_iss_id = ?,
                    ipa_par_code = ?,
                    ipa_created_date = ?';
        DB_Helper::getInstance()->query($sql, $params);

        $backend = Partner::getBackend($par_code);
        $backend->issueAdded($issueId);

        $usr_id = Auth::getUserID();
        History::add($issueId, $usr_id, 'partner_added', "Partner '{partner}' added to issue by {user}", [
            'partner' => $backend->getName(),
            'user' => User::getFullName($usr_id),
        ]);
    }

    private function removePartnerFromIssue(int $iss_id, string $par_code): void
    {
        $params = [$iss_id, $par_code];
        $sql = 'DELETE FROM
                    `issue_partner`
                WHERE
                    ipa_iss_id = ? AND
                    ipa_par_code = ?';
        DB_Helper::getInstance()->query($sql, $params);
        $backend = Partner::getBackend($par_code);
        $backend->issueRemoved($iss_id);

        $usr_id = Auth::getUserID();
        History::add($iss_id, $usr_id, 'partner_removed', "Partner '{partner}' removed from issue by {user}", [
            'partner' => $backend->getName(),
            'user' => User::getFullName($usr_id),
        ]);
    }
}
