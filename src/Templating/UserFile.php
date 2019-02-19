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

namespace Eventum\Templating;

use Smarty;

/**
 * Adds userscript.js, userstyle.css support.
 * Inspired by DokuWiki implementation.
 *
 * To add custom JavaScript or custom CSS to every eventum page,
 * Place "userscript.js", "userstyle.css" to "config/" dir
 * and they would be automatically included to every page.
 *
 * @see https://www.dokuwiki.org/devel:javascript
 * @see https://www.dokuwiki.org/devel:css#user_styles
 */
class UserFile
{
    /** @var Smarty */
    private $tpl;

    /** @var string */
    private $userdir;

    /**
     * @param Smarty $tpl
     * @param string $userdir
     */
    public function __construct($tpl, $userdir)
    {
        $this->tpl = $tpl;
        $this->userdir = $userdir;
    }

    public function __invoke(): void
    {
        $userfiles = [
            'userscript' => 'js',
            'userstyle' => 'css',
        ];

        foreach ($userfiles as $file => $type) {
            $content = $this->getContent($file, $type);
            $this->tpl->assign($file, $content ?: '');
        }
    }

    private function getContent($userfile, $type)
    {
        $filename = "{$this->userdir}/{$userfile}.{$type}";
        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }

        $this->tpl->assign("{$userfile}_content", $content);

        return $this->tpl->fetch("include/$userfile.tpl.html");
    }
}
