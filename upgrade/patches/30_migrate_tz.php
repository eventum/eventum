<?php
/**
 * Migrate timezone abbreviations to timezone identifier
 * The three letter timezone abbreviations do not play well with DST
 *
 * "2015-05-19 12:22:24 Europe/Tallinn"
 * "2015-05-19 12:22:24 EET"
 *
 * should both return same date in GMT, but instead they return:
 * "2015-05-19 09:22:24"
 * "2015-05-19 10:22:24"
 */

// build list of abbreviation => Timezone
// we take first timezone and hope it's correct
$timezones = array();
foreach (DateTimeZone::listAbbreviations() as $abbrevation => $list) {
    // take first timezone
    $timezone = current($list);
    $timezones[strtoupper($abbrevation)] = $timezone['timezone_id'];
}

/** @var DbInterface $db */
$res = $db->getAll("select upr_usr_id, upr_timezone from {{%user_preference}} where upr_timezone != ''");

foreach ($res as $row) {
    $usr_id = $row['upr_usr_id'];
    $tz = $row['upr_timezone'];

    // skip UTC
    if (in_array($tz, array('UTC', 'GMT'))) {
        continue;
    }

    // no mapping, sorry
    if (!isset($timezones[$tz])) {
        continue;
    }

    $prefs = Prefs::get($usr_id);
    $prefs['timezone'] = $timezones[$tz];
    echo "Updating user #$usr_id timezone: $tz => {$prefs['timezone']}\n";
    Prefs::set($usr_id, $prefs);
}
