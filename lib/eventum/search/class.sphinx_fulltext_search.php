<?php

require APP_INC_PATH . "/search/class.abstract_fulltext_search.php";
require APP_INC_PATH . "/search/sphinxapi.php";

class Sphinx_Fulltext_Search extends Abstract_Fulltext_Search
{
    private $sphinx;

    private $keywords;
    
    public function __construct()
    {
        $this->sphinx = new SphinxClient();
        $this->sphinx->SetServer(SPHINX_SEARCHD_HOST, SPHINX_SEARCHD_PORT);
        $this->matches = array();

        $this->match_mode = '';
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
//        $indexes = join('; ', $this->getIndexes());

        if ((isset($options['customer_id'])) && (!empty($options['customer_id']))) {
            $this->sphinx->SetFilter('customer_id', array($options['customer_id']));
        }

        $this->keywords = $options['keywords'];
        $this->match_mode = $options['match_mode'];

        $res = $this->sphinx->Query($options['keywords']);
//        echo $this->sphinx->getLastError();
//        echo "<pre>";print_r($res);
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
            "query_mode"    =>  $this->match_mode,
            'before_match'  => '<b>',
            'after_match'   => '</b>',
            'allow_empty'   =>  true,
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

    private function cleanUpExcerpt($str)
    {
        return Misc::removeNewLines($str);
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


    private function getIndexes()
    {
        return array(
                    'issue_description',
                    'issue_recent_description',
                    'email_description',
                    'email_recent_description',
                    'phonesupport_description',
                    'phonesupport_recent_description',
                    'note_description',
                    'note_recent_description',
        );
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