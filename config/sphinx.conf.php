#!/usr/bin/php
<?php

require_once __DIR__ . '/../init.php';

if (!defined('SPHINX_LOG_PATH')) {
    define('SPHINX_LOG_PATH', '/var/log/sphinx/');
}
if (!defined('SPHINX_RUN_PATH')) {
    define('SPHINX_RUN_PATH', '/var/run/sphinx/');
}
if (!defined('SPHINX_DATA_PATH')) {
    define('SPHINX_DATA_PATH', '/var/lib/sphinx/eventum/');
}
if (!defined('SPHINX_SEARCHD_PORT')) {
    define('SPHINX_SEARCHD_PORT', 3312);
}

$dbconfig = DB_Helper::getConfig();

// support localhost:/path/to/socket.sock syntax in db host
$sql_sock_enabled = '# ';
$sql_host = $dbconfig['hostname'];
$sql_sock = '';

$parts = explode(':', $sql_host, 2);
if (count($parts) >= 2 && list($host, $socket) = $parts) {
    $sql_sock_enabled = '';
    $sql_host = $host;
    $sql_sock = $socket;
}
?>

#############################################################################
## indexer settings
#############################################################################
indexer
{
    mem_limit            = 100M
}

#############################################################################
## searchd settings
#############################################################################
searchd
{
    listen       = <?php echo SPHINX_SEARCHD_PORT . "\n"; ?>
    log          = <?php echo SPHINX_LOG_PATH; ?>searchd-eventum.log
    query_log    = <?php echo SPHINX_LOG_PATH; ?>query-eventum.log
    read_timeout = 5
    max_children = 30
    pid_file     = <?php echo SPHINX_RUN_PATH; ?>searchd-eventum.pid
}

source eventum
{
    type                = mysql
    # connect over unix socket
    <?php echo $sql_sock_enabled; ?>sql_sock            = <?php echo $sql_sock . "\n"; ?>

    # connect over tcp
    sql_host            = <?php echo $sql_host . "\n"; ?>
    sql_port            = <?php echo $dbconfig['port'] . "\n"; ?>

    sql_user            = <?php echo $dbconfig['username'] . "\n"; ?>
    sql_pass            = <?php echo $dbconfig['password'] . "\n"; ?>
    sql_db              = <?php echo $dbconfig['database'] . "\n"; ?>
}


#############################################################################
## ISSUES
#############################################################################
source src_issue : eventum
{
    sql_query_range = SELECT MIN(iss_id), MAX(iss_id) FROM issue
    sql_query = \
        SELECT \
            iss_id, \
            iss_prj_id as prj_id, \
            1 as index_id, \
            iss_id as issue_id, \
            iss_customer_id as customer_id, \
            iss_customer_contract_id as contract_id, \
            UNIX_TIMESTAMP(iss_created_date) AS iss_created_date, \
            iss_summary, \
            iss_description \
        FROM \
            issue \
        WHERE \
            iss_id>=$start AND \
            iss_id<=$end
    sql_attr_uint   = index_id
    sql_attr_uint   = issue_id
    sql_attr_uint   = prj_id
    sql_attr_uint   = customer_id
    sql_attr_uint   = contract_id
    sql_attr_timestamp   = iss_created_date
    sql_query_pre      = SET NAMES utf8
}

index issue
{
    source              = src_issue
    path                = <?php echo SPHINX_DATA_PATH; ?>issue
    morphology          = none
    min_word_len        = 1
}

index issue_stemmed : issue
{
    path                = <?php echo SPHINX_DATA_PATH; ?>issue_stemmed
    morphology          = stem_en
}

# Combine the main and stemmed indexes together
index issue_description
{
    type                 = distributed
    local                = issue
    local                = issue_stemmed
}


#############################################################################
## ISSUES recent
#############################################################################
source src_issue_recent : src_issue
{
    sql_query_range = SELECT IF(CAST(MAX(iss_id) AS SIGNED)-100>0, MAX(iss_id), 1), MAX(iss_id) FROM issue
}

index issue_recent
{
    source              = src_issue_recent
    path                = <?php echo SPHINX_DATA_PATH; ?>issue_recent
    morphology          = none
    min_word_len        = 1
}

index issue_recent_stemmed : issue_recent
{
    path                = <?php echo SPHINX_DATA_PATH; ?>issue_recent_stemmed
    morphology          = stem_en
}

index issue_recent_description
{
    type                = distributed
    local               = issue_recent
    local               = issue_recent_stemmed
}


#############################################################################
## EMAILS
#############################################################################
source src_email : eventum
{
    sql_query_range = SELECT MIN(seb_sup_id), MAX(seb_sup_id) FROM support_email_body
    sql_range_step = 2000
    sql_query = \
        SELECT \
            seb_sup_id, \
            2 as index_id, \
            iss_id as issue_id, \
            iss_prj_id as prj_id, \
            iss_customer_id as customer_id, \
            iss_customer_contract_id as contract_id, \
            UNIX_TIMESTAMP(iss_created_date) AS iss_created_date, \
            sup_from, \
            sup_to, \
            sup_cc, \
            sup_subject, \
            seb_body \
        FROM \
            support_email, \
            support_email_body, \
            issue \
        WHERE \
            sup_iss_id = iss_id AND \
            seb_sup_id>=$start AND \
            seb_sup_id<=$end AND \
            sup_id = seb_sup_id
    sql_attr_uint       = index_id
    sql_attr_uint       = issue_id
    sql_attr_uint       = prj_id
    sql_attr_uint       = customer_id
    sql_attr_uint       = contract_id
    sql_attr_timestamp  = iss_created_date
    sql_query_pre       = SET NAMES utf8
}

index email
{
    source          = src_email
    path            = <?php echo SPHINX_DATA_PATH; ?>email
    morphology      = none
    min_word_len    = 1
}

index email_stemmed : email
{
    path            = <?php echo SPHINX_DATA_PATH; ?>email_stemmed
    morphology      = stem_en
}

index email_description
{
    type                   = distributed
    local                  = email
    local                  = email_stemmed
}

#############################################################################
## EMAILS RECENT (Only last 1000 records)
#############################################################################
source src_email_recent : src_email
{
#    sql_query_range = SELECT (MAX(seb_sup_id)-1000), MAX(seb_sup_id) FROM support_email_body
#    # we use @max to store initial value and not to overflow
#    sql_query_range = SELECT (@max:=MAX(seb_sup_id))- 1000, MAX(seb_sup_id) FROM support_email_body
#    # we use need to cast MAX() result to SIGNED not to overflow and check for negative value
    sql_query_range = SELECT IF(CAST(MAX(sup_id) AS SIGNED)-1000>0, MAX(sup_id), 1), MAX(sup_id) FROM support_email
}

index email_recent
{
    source          = src_email_recent
    path            = <?php echo SPHINX_DATA_PATH; ?>email_recent
    morphology      = none
    stopwords       =
    min_word_len    = 1
}

index email_recent_stemmed : email_recent
{
    path            = <?php echo SPHINX_DATA_PATH; ?>email_recent_stemmed
    morphology      = stem_en
}

index email_recent_description
{
    type                   = distributed
    local                  = email_recent
    local                  = email_recent_stemmed
}


#############################################################################
## PHONE SUPPORT
#############################################################################
source src_phonesupport : eventum
{
    sql_query_range = SELECT MIN(phs_id), MAX(phs_id) FROM phone_support
    sql_range_step = 1000
    sql_query = \
        SELECT \
            phs_id, \
            3 as index_id, \
            iss_id as issue_id, \
            iss_prj_id as prj_id, \
            iss_customer_id as customer_id, \
            iss_customer_contract_id as contract_id, \
            UNIX_TIMESTAMP(iss_created_date) AS iss_created_date, \
            /*phs_call_from AS call_name_from, */ \
            /*phs_call_to as call_name_to, */ \
            phs_phone_number, \
            phs_description/*, \
            phs_triggered_by_other */\
        FROM \
            phone_support, \
            issue \
        WHERE \
            phs_iss_id = iss_id AND \
            phs_id>=$start AND phs_id<=$end
    sql_attr_uint       = index_id
    sql_attr_uint       = issue_id
    sql_attr_uint       = prj_id
    sql_attr_uint       = customer_id
    sql_attr_uint       = contract_id
    sql_attr_timestamp  = iss_created_date
    sql_query_pre       = SET NAMES utf8
}

index phonesupport
{
    source              = src_phonesupport
    path                = <?php echo SPHINX_DATA_PATH; ?>phonesupport
    morphology          = none
    min_word_len        = 1
}

index phonesupport_stemmed : phonesupport
{
    path                = <?php echo SPHINX_DATA_PATH; ?>phonesupport_stemmed
    morphology          = stem_en
}

index phonesupport_description
{
    type                = distributed
    local               = phonesupport
    local               = phonesupport_stemmed
}


#############################################################################
## PHONE SUPPORT RECENT (Only last 1000 records)
#############################################################################
source src_phonesupport_recent : src_phonesupport
{
#    sql_query_range = SELECT (MAX(phs_id)-1000), MAX(phs_id) FROM phone_support
#    # we use @max:= to avoid overflow via cast
#    sql_query_range = SELECT (@max:=MAX(phs_id))-1000, MAX(phs_id) FROM phone_support
    sql_query_range = SELECT IF(CAST(MAX(phs_id) AS SIGNED)-1000>0, MAX(phs_id), 1), MAX(phs_id) FROM phone_support

}

index phonesupport_recent
{
    source              = src_phonesupport_recent
    path                = <?php echo SPHINX_DATA_PATH; ?>phonesupport_recent
    morphology          = none
    min_word_len        = 1
}

index phonesupport_recent_stemmed : phonesupport_recent
{
    path                = <?php echo SPHINX_DATA_PATH; ?>phonesupport_recent_stemmed
    morphology          = stem_en
}

index phonesupport_recent_description
{
    type                = distributed
    local               = phonesupport_recent
    local               = phonesupport_recent_stemmed
}


#############################################################################
## NOTES data source definition
#############################################################################
source src_note : eventum
{
    sql_query_range = SELECT MIN(not_id), MAX(not_id) FROM note
    sql_range_step = 1000
    sql_query = \
        SELECT \
            not_id, \
            4 as index_id, \
            iss_id as issue_id, \
            iss_prj_id as prj_id, \
            iss_customer_id as customer_id, \
            iss_customer_contract_id as contract_id, \
            UNIX_TIMESTAMP(iss_created_date) AS iss_created_date, \
            not_title, \
            not_note /*, \
            not_blocked_message*/ \
        FROM \
            note, \
            issue \
        WHERE \
            not_iss_id = iss_id AND \
            not_removed = 0 AND \
            not_id>=$start AND not_id<=$end
    sql_attr_uint       = index_id
    sql_attr_uint       = issue_id
    sql_attr_uint       = prj_id
    sql_attr_uint       = customer_id
    sql_attr_uint       = contract_id
    sql_attr_timestamp  = iss_created_date
    sql_query_pre       = SET NAMES utf8
}

index note
{
    source              = src_note
    path                = <?php echo SPHINX_DATA_PATH; ?>note
    morphology          = none
    min_word_len        = 1
}

index note_stemmed : note
{
    path                = <?php echo SPHINX_DATA_PATH; ?>note_stemmed
    morphology          = stem_en
}

index note_description
{
    type                = distributed
    local               = note
    local               = note_stemmed
}


#############################################################################
## NOTES RECENT (Only last 1000 notes)
#############################################################################
source src_note_recent : src_note
{
#    sql_query_range = SELECT (MAX(not_id)-1000), MAX(not_id) FROM note
#    # we use @max:= to avoid overflow via cast
#    sql_query_range = SELECT (@max:=MAX(not_id))-1000, MAX(not_id) FROM note
    sql_query_range = SELECT IF(CAST(MAX(not_id) AS SIGNED)-1000>0, MAX(not_id), 1), MAX(not_id) FROM note
}

index note_recent
{
    source              = src_note_recent
    path                = <?php echo SPHINX_DATA_PATH; ?>note_recent
    morphology          = none
    min_word_len        = 1
}

index note_recent_stemmed : note_recent
{
    path                = <?php echo SPHINX_DATA_PATH; ?>note_recent_stemmed
    morphology          = stem_en
}

index note_recent_description
{
    type                = distributed
    local               = note_recent
    local               = note_recent_stemmed
}
