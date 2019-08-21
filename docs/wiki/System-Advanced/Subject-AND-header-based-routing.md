### Subject AND header based routing

In 1.7.0, if subject based routing is enabled, this disables header based routing. This patch lets Eventum check the headers if no issue number is found in the subject. Catch both cases = less manual email association. -- Eliot B

    --- eventum-1.7.0/include/class.support.php     2005-12-30 08:27:24.000000000 +1300
    +++ eventum/include/class.support.php   2006-02-22 20:09:09.000000000 +1300
    @@ -737,11 +739,10 @@
             $message_id = Mail_API::getMessageID($headers, $message_body);

             $setup = Setup::load();
    -        if (@$setup['subject_based_routing']['status'] == 'enabled') {
    -            // Look for issue ID in the subject line
    -
    -            // look for [#XXXX] in the subject line
    -            if (preg_match("/\[#(\d+)\]( Note| BLOCKED)*/", $subject, $matches)) {
    +        if ((@$setup['subject_based_routing']['status'] == 'enabled') &&
    +            // Look for issue ID [#nnnn] in the subject line
    +             (preg_match("/\[#(\d+)\]( Note| BLOCKED)*/", $subject, $matches)))
    +       {
                     $should_create_issue = false;
                     $issue_id = $matches[1];
                     if (!Issue::exists($issue_id, false)) {
    @@ -749,7 +750,6 @@
                     } elseif (!empty($matches[2])) {
                         $type = 'note';
                     }
    -            }
             } else {
                 // - if this email is a reply:
                 if (count($references) > 0) {
