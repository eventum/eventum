<?php
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.draft.php");
include_once(APP_INC_PATH . "jsrsServer.inc.php");

/*
 * This page is used to return a single content to the expandable table using jsrs.
 */

jsrsDispatch("getEmail,getNote,getDraft");

/**
 * Selects the email from the table and returns the contents. Since jsrs only supports returning one value, 
 * the string that is returned is in the format
 * of ec_id:id:email. If ec_id is not passed as a parameter, only the email is returned.
 * 
 * @param   string $id The sup_ema_id and sup_id seperated by a -.
 * @return  A string containing the body of the email, optionally prefaced by the ec_id and $id.
 */
function getEmail($id)
{
    $split = explode("-", $id);
    $info = Support::getEmailDetails($split[0],$split[1]);
    if (!empty($_GET["ec_id"])) {
        return nl2br(htmlspecialchars($_GET["ec_id"] . ":" . $id. ":" . $info["sup_body"]));
    } else {
        return $info["sup_body"];
    }
}


/**
 * Selects a note from the table and returns the contents.
 * 
 * @param   string $id The ID of this note.
 * @return  A string containing the note.
 */
function getNote($id)
{
    $note = Note::getDetails($id);
    if (!empty($_GET["ec_id"])) {
        return nl2br(htmlspecialchars($_GET["ec_id"] . ":" . $id. ":" . $note["not_note"]));
    } else {
        return $note["not_note"];
    }
}


/**
 * Selects a draft from the table and returns the contents.
 * 
 * @param   string $id The ID of this draft.
 * @return  A string containing the note.
 */
function getDraft($id)
{
    $info = Draft::getDetails($id);
    if (!empty($_GET["ec_id"])) {
        return nl2br(htmlspecialchars($_GET["ec_id"] . ":" . $id. ":" . $info["emd_body"]));
    } else {
        return $info["emd_body"];
    }
}
?>