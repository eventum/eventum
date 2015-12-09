<?php

class HistoryTest extends TestCase
{
    public function testHistoryContext()
    {
        $message = "Issue updated to status '{status}' by {actor}";
        $context = array(
            'status' => 'closed',
            'actor' => 'Random User',
        );
        $message = Misc::processTokens($message, $context);
        $exp = "Issue updated to status 'closed' by Random User";
        $this->assertEquals($exp, $message);
    }
}
