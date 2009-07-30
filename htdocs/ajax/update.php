<?
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
require_once(dirname(__FILE__) . '/../../init.php');
require_once(APP_INC_PATH . "/class.auth.php");
require_once(APP_INC_PATH . "/class.issue.php");

// check login
if (!Auth::hasValidCookie(APP_COOKIE)) {
    exit;
}

if (!is_numeric($_POST['issueID'])) {
    exit;
}

switch ($_POST['fieldName']) {
    case 'expected_resolution_date':
        $day = (int)$_POST['day'];
        $month = (int)$_POST['month'];
        $year = (int)$_POST['year'];

        if ($day == 0 && $month == 1 && $year == 0) {
            // clear button
            $date = null;
        } else {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        if (Issue::updateField($_POST['issueID'], $_POST['fieldName'], $date) !== -1) {
            if (!is_null($date)) {
                echo Date_Helper::getSimpleDate(sprintf('%04d-%02d-%02d', $year, $month, $day), false);
            }
        } else {
            echo 'update failed';
        }

        exit;
    break;

    default:
        die('object type not supported');
    break;
}
