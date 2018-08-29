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

use Eventum\Db\AbstractMigration;
use Eventum\Mail\MailMessage;

/**
 * Fixes note and email tables
 * where legacy code filled subject as Array
 * when email contained duplicate "Subject:" headers.
 */
class EventumDuplicateSubject extends AbstractMigration
{
    public function up()
    {
        $entries = $this->getEmailEntries();
        foreach ($this->getIterator($entries) as $id) {
            $mail = $this->getEmail($id);
            $this->setEmailSubject($id, $mail->subject);
        }
        $count = count($entries);
        $this->writeln("Fixed $count emails");

        $entries = $this->getNoteEntries();
        foreach ($this->getIterator($entries) as $id) {
            $mail = $this->getNote($id);
            $this->setNoteTitle($id, $mail->subject);
        }
        $count = count($entries);
        $this->writeln("Fixed $count notes");
    }

    private function getIterator($entries)
    {
        $progressBar = $this->createProgressBar(count($entries));
        foreach ($entries as $entry) {
            yield $entry;
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->writeln('');
    }

    private function getEmailEntries($idColumn = 'sup_id')
    {
        $sql = "select `$idColumn` from `support_email` where `sup_subject`='Array'";

        return $this->queryColumn($sql, $idColumn);
    }

    private function getNoteEntries($idColumn = 'not_id')
    {
        $sql = "select `$idColumn` from `note` where `not_title`='Array'";

        return $this->queryColumn($sql, $idColumn);
    }

    private function getEmail($id, $emailField = 'seb_full_email')
    {
        $sql = "select `$emailField` from `support_email_body` where seb_sup_id=$id";

        $raw = $this->queryOne($sql, $emailField);

        return MailMessage::createFromString($raw);
    }

    private function getNote($id, $emailField = 'not_full_message')
    {
        $sql = "select `$emailField` from `note` where not_id=$id";

        $raw = $this->queryOne($sql, $emailField);

        return MailMessage::createFromString($raw);
    }

    private function setEmailSubject($id, $subject)
    {
        $subject = $this->quote($subject);
        $sql = "UPDATE `support_email` SET sup_subject=$subject WHERE sup_id=$id";

        $this->query($sql);
    }

    private function setNoteTitle($id, $title)
    {
        $title = $this->quote($title);
        $sql = "UPDATE `note` SET not_title=$title WHERE not_id=$id";

        $this->query($sql);
    }
}
