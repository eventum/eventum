<?php

class HistoryTest extends PHPUnit_Framework_TestCase
{
    public function testHistoryContext()
    {
        $message = "Issue updated to status '{status}' by {actor}";
        $context = array(
            'status' => 'closed',
            'actor' => 'Random User',
        );
        $message = $this->logProcessor($message, $context);
        $exp = "Issue updated to status 'closed' by Random User";
        $this->assertEquals($exp, $message);
    }

    /**
     * @see \Monolog\Processor\PsrLogMessageProcessor()
     * @link https://github.com/Seldaek/monolog/blob/master/src/Monolog/Processor/PsrLogMessageProcessor.php
     * @param string $message
     * @param  array $context
     * @return string
     */
    private function logProcessor($message, array $context)
    {
        if (false === strpos($message, '{')) {
            return $message;
        }

        $replacements = array();
        foreach ($context as $key => $val) {
            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{' . $key . '}'] = $val;
            } elseif (is_object($val)) {
                $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
            } else {
                $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
            }
        }

        $message = strtr($message, $replacements);

        return $message;
    }
}
