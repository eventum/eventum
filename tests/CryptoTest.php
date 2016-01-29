<?php

use Eventum\Crypto\CryptoManager;

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
     * and the object is not dumpable so that plain text value is visible in backtraces
     */
    public function testEncryptedValue()
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        $value = new CryptoManager($encrypted);
        $this->assertEquals($plaintext, $value);
    }
}
