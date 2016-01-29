<?php

use Eventum\EncryptedValue;

class EncryptedValueTest extends TestCase
{
    public function testEncrypt()
    {
        $plaintext = 'tore';
        $encrypted = EncryptedValue::encrypt($plaintext);
        var_dump($encrypted);
        $decrypted = EncryptedValue::decrypt($encrypted);
        var_dump($decrypted);
        $this->assertSame($plaintext, $decrypted);
    }
}
