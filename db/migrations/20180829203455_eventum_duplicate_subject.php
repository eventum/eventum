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
    public function up(): void
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
        $total = count($entries);
        if (!$total) {
            return;
        }

        $progressBar = $this->createProgressBar($total);
        $progressBar->start();
        foreach ($entries as $entry) {
            yield $entry;
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->writeln('');
    }

    private function getEmailEntries($idColumn = 'sup_id'): array
    {
        $sql = "select `$idColumn` from `support_email` where `sup_subject`='Array'";

        return $this->queryColumn($sql, $idColumn);
    }

    private function getNoteEntries($idColumn = 'not_id'): array
    {
        $sql = "select `$idColumn` from `note` where `not_title`='Array'";

        return $this->queryColumn($sql, $idColumn);
    }

    private function getEmail($id, $emailField = 'seb_full_email'): MailMessage
    {
        $sql = "select `$emailField` from `support_email_body` where seb_sup_id=$id";

        $raw = $this->queryOne($sql, $emailField);
        if (!$raw) {
            throw new RuntimeException("Unable to load mail: #$id");
        }

        return MailMessage::createFromString($raw);
    }

    private function getNote($id, $emailField = 'not_full_message'): MailMessage
    {
        $sql = "select `$emailField` from `note` where not_id=$id";

        $raw = $this->queryOne($sql, $emailField);
        if (!$raw) {
            throw new RuntimeException("Unable to load note: #$id");
        }

        return MailMessage::createFromString($raw);
    }

    private function setEmailSubject($id, $subject): void
    {
        $subject = $this->quote($subject);
        $sql = "UPDATE `support_email` SET sup_subject=$subject WHERE sup_id=$id";

        $this->query($sql);
    }

    private function setNoteTitle($id, $title): void
    {
        $title = $this->quote($title);
        $sql = "UPDATE `note` SET not_title=$title WHERE not_id=$id";

        $this->query($sql);
    }
}
