<?php

class Sphinx_Fulltext_Search extends Abstract_Fulltext_Search
{
    private $sphinx;

    private $keywords;
	private $excerpt_placeholder;

    public function __construct()
    {
        $this->sphinx = new SphinxClient();
        $this->sphinx->SetServer(SPHINX_SEARCHD_HOST, SPHINX_SEARCHD_PORT);
        $this->matches = array();

        $this->match_mode = '';

		// generate unique placeholder
        $this->excerpt_placeholder = 'excerpt' . rand(). 'placeholder';
    }


    public function getIssueIDs($options)
    {
        // Build the Sphinx client
        $this->sphinx->SetSortMode(SPH_SORT_RELEVANCE);
//        $this->sphinx->SetWeights(array(1, 1));
        $this->sphinx->SetLimits(0,500, 100000);
        $this->sphinx->SetArrayResult(true);

        if (empty($options['match_mode'])) {
            $options['match_mode'] = SPH_MATCH_ALL;
        }
        $this->sphinx->SetMatchMode($options['match_mode']);

        $this->sphinx->SetFilter('prj_id', array(Auth::getCurrentProject()));

        // TODO: Add support for selecting indexes to search
        $indexes = join('; ', $this->getIndexes((Auth::getCurrentRole() > User::getRoleID("Customer"))));

        if ((isset($options['customer_id'])) && (!empty($options['customer_id']))) {
            $this->sphinx->SetFilter('customer_id', array($options['customer_id']));
        }

        $this->keywords = $options['keywords'];
        $this->match_mode = $options['match_mode'];

        $res = $this->sphinx->Query($options['keywords'], $indexes);

		// TODO: report these somehow back to the UI
		if (method_exists($this->sphinx, 'IsConnectError') && $this->sphinx->IsConnectError()) {
			error_log("sphinx_fulltext_search: Network Error");
		}
		if ($this->sphinx->GetLastWarning()) {
			error_log("sphinx_fulltext_search: WARNING: " . $this->sphinx->GetLastWarning());
		}
		if ($this->sphinx->GetLastError()) {
			error_log("sphinx_fulltext_search: ERROR: " . $this->sphinx->GetLastError());
		}

        $issue_ids = array();
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

                $this->matches[$issue_id][] = array(
                    'weight'    =>  $weight,
                    'index'     =>  $index_name,
                    'match_id'  =>  $match_id,
                );

                $issue_ids[] = $issue_id;
            }
        }

        return $issue_ids;
    }

    public function getExcerpts()
    {
        if (count($this->matches) < 1) {
            return false;
        }

        $excerpt_options = array(
            'query_mode'    => $this->match_mode,
            'before_match'  => $this->excerpt_placeholder . '-before',
            'after_match'   => $this->excerpt_placeholder . '-after',
            'allow_empty'   => true,
        );
        $excerpts = array();
        foreach ($this->matches as $issue_id => $matches) {
            $excerpt = array(
                'issue' =>  array(),
                'email' =>  array(),
                'phone' =>  array(),
                'note'  =>  array(),
            );
            foreach ($matches as $match) {
                if ($match['index'] == 'issue') {
                    $issue = Issue::getDetails($issue_id);
                    $documents = array($issue['iss_summary']);
                    $res = $this->sphinx->BuildExcerpts($documents, 'issue_stemmed', $this->keywords, $excerpt_options);
                    if ($res[0] != $issue['iss_summary']) {
                        $excerpt['issue']['summary'] = self::cleanUpExcerpt($res[0]);
                    }

                    $documents = array($issue['iss_original_description']);
                    $res = $this->sphinx->BuildExcerpts($documents, 'issue_stemmed', $this->keywords, $excerpt_options);
                    if ($res[0] != $issue['iss_original_description']) {
                        $excerpt['issue']['description'] = self::cleanUpExcerpt($res[0]);
                        error_log(print_r($excerpt['issue']['description'],1));
                    }
                } elseif ($match['index'] == 'email') {
                    $email = Support::getEmailDetails(null, $match['match_id']);
                    $documents = array($email['sup_subject'] . "\n" . $email['message']);
                    $res = $this->sphinx->BuildExcerpts($documents, 'email_stemmed', $this->keywords, $excerpt_options);
                    $excerpt['email'][Support::getSequenceByID($match['match_id'])] = self::cleanUpExcerpt($res[0]);
                } elseif ($match['index'] == 'phone') {
                    $phone_call = Phone_Support::getDetails($match['match_id']);
                    $documents = array($phone_call['phs_description']);
                    $res = $this->sphinx->BuildExcerpts($documents, 'phonesupport_stemmed', $this->keywords, $excerpt_options);
                    $excerpt['phone'][] = self::cleanUpExcerpt($res[0]);
                } elseif ($match['index'] == 'note') {
                    $note = Note::getDetails($match['match_id']);
                    $documents = array($note['not_title'] . "\n" . $note['not_note']);
                    $res = $this->sphinx->BuildExcerpts($documents, 'note_stemmed', $this->keywords, $excerpt_options);
                    $excerpt['note'][Note::getNoteSequenceNumber($issue_id, $match['match_id'])] = self::cleanUpExcerpt($res[0]);
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
	 */
	private function cleanUpExcerpt($str)
	{
		return str_replace(
				array(
					$this->excerpt_placeholder . '-before',
					$this->excerpt_placeholder . '-after',
				),
				array('<b>', '</b>'),
				htmlspecialchars(Misc::removeNewLines($str)
			));
	}

    public function getMatchModes()
    {
        return array(
            SPH_MATCH_ALL   =>  'All Words',
            SPH_MATCH_ANY   =>  'Any Word',
            SPH_MATCH_PHRASE    =>  'Phrase',
            SPH_MATCH_BOOLEAN   =>  'Boolean',
            SPH_MATCH_EXTENDED2 =>  'Extended',
        );
    }


    private function getIndexes($all_indexes=false)
    {
        $indexes = array(
                    'issue',
                    'issue_stemmed',
                    'issue_recent',
                    'issue_recent_stemmed',
                    'email',
                    'email_stemmed',
                    'email_recent',
                    'email_recent_stemmed',
        );

        if ($all_indexes) {
            $indexes = array_merge($indexes, array(
                    'note',
                    'note_stemmed',
                    'note_recent',
                    'note_recent_stemmed',
                    'phonesupport',
                    'phonesupport_stemmed',
                    'phonesupport_recent',
                    'phonesupport_recent_stemmed',
            ));
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

}
