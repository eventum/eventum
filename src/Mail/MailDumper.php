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

namespace Eventum\Mail;

use DateTime;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class MailDumper
{
    /** Similar to RFC3339_EXTENDED, but filesystem safe */
    private const DATE_FORMAT = 'Y-m-d_H.i.s.u';

    /** @var string */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Method used to save the routed mail into a backup directory.
     *
     * @throws IOException
     */
    public function dump(MailMessage $mail): void
    {
        $filename = $this->getFilename($mail);
        $fs = new Filesystem();
        $fs->dumpFile($filename, $mail->getRawContent());
        $fs->chmod($filename, 0644);
    }

    private function getFilename(MailMessage $mail): string
    {
        $ts = new DateTime();
        $mid = trim($mail->messageId ?: 'unknown', '<>');

        return sprintf("%s/%s_$mid.txt", $this->path, $ts->format(self::DATE_FORMAT));
    }
}
