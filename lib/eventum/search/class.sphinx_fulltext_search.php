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

use Eventum\Monolog\Logger;
use Psr\Log\LoggerInterface;

class Sphinx_Fulltext_Search extends Abstract_Fulltext_Search
{
    /** @var SphinxClient */
    private $sphinx;

    private $keywords;
    /** @var string */
    private $excerpt_placeholder;
    private $matches = [];
    private $match_mode = '';
    /** @var LoggerInterface */
    private $logger;

    public function __construct()
    {
        $this->sphinx = new SphinxClient();
        $this->sphinx->SetServer(SPHINX_SEARCHD_HOST, SPHINX_SEARCHD_PORT);

        // generate unique placeholder
        $this->excerpt_placeholder = 'excerpt' . rand() . 'placeholder';
        $this->logger = Logger::app();
    }

    public function getIssueIDs($options)
    {
        // Build the Sphinx client
        $this->sphinx->SetSortMode(SPH_SORT_RELEVANCE);
//        $this->sphinx->SetWeights(array(1, 1));
        $this->sphinx->SetLimits(0, 5000, 100000);
        $this->sphinx->SetArrayResult(true);

        if (empty($options['match_mode'])) {
            $options['match_mode'] = SPH_MATCH_ALL;
        }
        $this->sphinx->SetMatchMode($options['match_mode']);

        $this->sphinx->SetFilter('prj_id', [Auth::getCurrentProject()]);

        // TODO: Add support for selecting indexes to search
        $indexes = implode('; ', $this->getIndexes((Auth::getCurrentRole() > User::ROLE_CUSTOMER)));

        if ((isset($options['customer_id'])) && (!empty($options['customer_id']))) {
            $this->sphinx->SetFilter('customer_id', [$options['customer_id']]);
        }

        $this->keywords = $options['keywords'];
        $this->match_mode = $options['match_mode'];

        $res = $this->sphinx->Query($options['keywords'], $indexes);

        // TODO: report these somehow back to the UI
        // probably easy to do with Logger framework (add new handler?)
        if (method_exists($this->sphinx, 'IsConnectError') && $this->sphinx->IsConnectError()) {
            $this->logger->error('sphinx_fulltext_search: Network Error');
        }
        if ($this->sphinx->GetLastWarning()) {
            $this->logger->warning('sphinx_fulltext_search: ' . $this->sphinx->GetLastWarning());
        }
        if ($this->sphinx->GetLastError()) {
            $this->logger->error('sphinx_fulltext_search: ' . $this->sphinx->GetLastError());
        }

        $issue_ids = [];
        if (isset($res['matches'])) {
            foreach ($res['matches'] as $match_details) {
                // Variable translation
                $match_id = $match_details['id'];
                $issue_id = $match_details['attrs']['issue_id'];
                $weight = $match_details['weight'];
                $index_id = $match_details['attrs']['index_id'];

                // if sphinx returns 0 as a weight, make it one because it
                // did find a match in the result set
                if ($weight <= 0) {
                    $weight = 1;
                }
                $index_name = $this->getIndexNameByID($index_id);

                $this->matches[$issue_id][] = [
                    'weight' => $weight,
                    'index' => $index_name,
                    'match_id' => $match_id,
                ];

                $issue_ids[] = $issue_id;
            }
        }

        return array_unique($issue_ids);
    }

    public function getExcerpts()
    {
        if (count($this->matches) < 1) {
            return false;
        }

        $excerpt_options = [
            'query_mode' => $this->match_mode,
            'before_match' => $this->excerpt_placeholder . '-before',
            'after_match' => $this->excerpt_placeholder . '-after',
            'allow_empty' => true,
        ];
        $excerpts = [];
        foreach ($this->matches as $issue_id => $matches) {
            $excerpt = [
                'issue' => [],
                'email' => [],
                'phone' => [],
                'note' => [],
            ];
            foreach ($matches as $match) {
                if ($match['index'] == 'issue') {
                    $issue = Issue::getDetails($issue_id);
                    $documents = [$issue['iss_summary']];
                    $res = $this->sphinx->BuildExcerpts($documents, 'issue_stemmed', $this->keywords, $excerpt_options);
                    if ($res[0] != $issue['iss_summary']) {
                        $excerpt['issue']['summary'] = self::cleanUpExcerpt($res[0]);
                    }

                    $documents = [$issue['iss_original_description']];
                    $res = $this->sphinx->BuildExcerpts($documents, 'issue_stemmed', $this->keywords, $excerpt_options);
                    if ($res[0] != $issue['iss_original_description']) {
                        $excerpt['issue']['description'] = self::cleanUpExcerpt($res[0]);
                    }
                } elseif ($match['index'] == 'email') {
                    try {
                        $email = Support::getEmailDetails($match['match_id']);
                        $documents = [$email['sup_subject'] . "\n" . $email['message']];
                        $res = $this->sphinx->BuildExcerpts($documents, 'email_stemmed', $this->keywords, $excerpt_options);
                        $excerpt['email'][Support::getSequenceByID($match['match_id'])] = self::cleanUpExcerpt($res[0]);
                    } catch (Zend\Mail\Header\Exception\InvalidArgumentException $e) {
                        $this->logger->error("Error loading email {$match['match_id']}", $match);
                    }
                } elseif ($match['index'] == 'phone') {
                    $phone_call = Phone_Support::getDetails($match['match_id']);
                    $documents = [$phone_call['phs_description']];
                    $res = $this->sphinx->BuildExcerpts($documents, 'phonesupport_stemmed', $this->keywords, $excerpt_options);
                    $excerpt['phone'][] = self::cleanUpExcerpt($res[0]);
                } elseif ($match['index'] == 'note') {
                    $note = Note::getDetails($match['match_id']);
                    $documents = [$note['not_title'] . "\n" . $note['not_note']];
                    $res = $this->sphinx->BuildExcerpts($documents, 'note_stemmed', $this->keywords, $excerpt_options);
                    $note_seq = Note::getNoteSequenceNumber($issue_id, $match['match_id']);
                    $excerpt['note'][$note_seq] = self::cleanUpExcerpt($res[0]);
                }
            }

            foreach ($excerpt as $key => $val) {
                if (count($val) < 1) {
                    unset($excerpt[$key]);
                }
            }

            $excerpts[$issue_id] = $excerpt;
        }

        return $excerpts;
    }

    /**
     * Cleanup excerpt from newlines.
     *
     * Converts placeholders to HTML bold tags and returns text HTML encoded
     *
     * @param string $str
     * @return string
     */
    private function cleanUpExcerpt($str)
    {
        return str_replace(
                [
                    $this->excerpt_placeholder . '-before',
                    $this->excerpt_placeholder . '-after',
                ],
                ['<b>', '</b>'],
                htmlspecialchars(Misc::removeNewLines($str)
            ));
    }

    public function getMatchModes()
    {
        return [
            SPH_MATCH_ALL => 'All Words',
            SPH_MATCH_ANY => 'Any Word',
            SPH_MATCH_PHRASE => 'Phrase',
            SPH_MATCH_BOOLEAN => 'Boolean',
            SPH_MATCH_EXTENDED2 => 'Extended',
        ];
    }

    private function getIndexes($all_indexes = false)
    {
        $indexes = [
                    'issue',
                    'issue_stemmed',
                    'issue_recent',
                    'issue_recent_stemmed',
                    'email',
                    'email_stemmed',
                    'email_recent',
                    'email_recent_stemmed',
        ];

        if ($all_indexes) {
            $indexes = array_merge($indexes, [
                    'note',
                    'note_stemmed',
                    'note_recent',
                    'note_recent_stemmed',
                    'phonesupport',
                    'phonesupport_stemmed',
                    'phonesupport_recent',
                    'phonesupport_recent_stemmed',
            ]);
        }

        return $indexes;
    }

    private function getIndexNameByID($id)
    {
        switch ($id) {
            case 1:
                return 'issue';
            case 2:
                return 'email';
            case 3:
                return 'phone';
            case 4:
                return 'note';
            default:
                return false;
        }
    }

    public function supportsExcerpts()
    {
        return true;
    }
}
