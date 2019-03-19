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

namespace Eventum\Controller;

use Auth;
use RuntimeException;

class SpellCheckController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'spell_check.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $request = $this->getRequest();
        $form_name = $request->query->get('form_name');

        if ($form_name) {
            // show temporary form
            $this->tpl->assign('show_temp_form', 'yes');
        } else {
            $textarea = $request->query->get('textarea');
            $this->tpl->assign('spell_check', $this->checkSpelling($textarea));
        }
    }

    /**
     * Method used to check the spelling of a given text.
     *
     * @param   string $text The text to check the spelling against
     * @return  array Information about the misspelled words, if any
     */
    private function checkSpelling($text)
    {
        $temptext = tempnam('/tmp', 'spelltext');
        $fd = fopen($temptext, 'w');
        if (!$fd) {
            throw new RuntimeException('Could not open temp file for write');
        }
        $textarray = explode("\n", $text);
        fwrite($fd, "!\n");
        foreach ($textarray as $value) {
            // adding the carat to each line prevents the use of aspell commands within the text...
            fwrite($fd, "^$value\n");
        }
        fclose($fd);
        $return = shell_exec("cat $temptext | /usr/bin/aspell -a");
        unlink($temptext);

        $lines = explode("\n", $return);
        // remove the first line that is only the aspell copyright banner
        array_shift($lines);
        // remove all blank lines
        foreach ($lines as $key => $value) {
            if (empty($value)) {
                unset($lines[$key]);
            }
        }
        $lines = array_values($lines);

        $misspelled_words = [];
        $spell_suggestions = [];
        foreach ($lines as $line) {
            if (substr($line, 0, 1) == '&') {
                // found suggestions for this word
                $first_part = substr($line, 0, strpos($line, ':'));
                $pieces = explode(' ', $first_part);
                $misspelled_word = $pieces[1];
                $last_part = substr($line, strpos($line, ':') + 2);
                $suggestions = explode(', ', $last_part);
            } elseif (substr($line, 0, 1) == '#') {
                // found no suggestions for this word
                $pieces = explode(' ', $line);
                $misspelled_word = $pieces[1];
                $suggestions = [];
            } else {
                // no spelling mistakes could be found
                continue;
            }
            // prevent duplicates...
            if (in_array($misspelled_word, $misspelled_words)) {
                continue;
            }
            $misspelled_words[] = $misspelled_word;
            $spell_suggestions[$misspelled_word] = $suggestions;
        }

        return [
            'total_words' => count($misspelled_words),
            'words' => $misspelled_words,
            'suggestions' => $spell_suggestions,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
