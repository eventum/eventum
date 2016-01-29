<?php

use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;

class CryptoTest extends TestCase
{
    /**
     * @test static encrypt and decrypt methods
     */
    public function testEncryptStatic()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);
        var_dump($encrypted);
        $decrypted = CryptoManager::decrypt($encrypted);
        var_dump($decrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test object instance which behaves as string, i.e __toString will return decrypted value
     */
    public function testEncryptedValue()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        $value = new EncryptedValue($encrypted);
        $this->assertEquals($plaintext, (string)$value, "test that casting to string calls tostring");
        $this->assertEquals($plaintext, $value, "test that not casting also works");

        // test getEncrypted method
        $this->assertEquals($encrypted, $value->getEncrypted());
    }

    /**
     * Test that the object plain text value is not visible in backtraces
     */
    public function testTraceVisibility()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);
        $value = new EncryptedValue($encrypted);

        $f = function ($e) {
            $f = function() {
                $e = new Exception('boo');
                return $e->getTraceAsString();
            };
            return $f($e);
        };

        $trace = $f($value);
        $this->assertNotContains($plaintext, $trace);
    }
}
