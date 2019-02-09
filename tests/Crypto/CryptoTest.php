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

namespace Eventum\Test\Crypto;

use Eventum;
use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;
use Eventum\Test\TestCase;
use Exception;
use InvalidArgumentException;

/**
 * @group crypto
 */
class CryptoTest extends TestCase
{
    public function testCanEncrypt(): void
    {
        $res = CryptoManager::canEncrypt();
        $this->assertTrue($res);
    }

    /**
     * test static encrypt and decrypt methods
     */
    public function testEncryptStatic(): void
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);
        $decrypted = CryptoManager::decrypt($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test object instance which behaves as string, i.e __toString will return decrypted value
     */
    public function testEncryptedValue(): void
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        $value = new EncryptedValue($encrypted);
        $this->assertEquals($plaintext, (string)$value, 'test that casting to string calls tostring');
        $this->assertEquals($plaintext, $value, 'test that not casting also works');

        // test getEncrypted method
        $this->assertEquals($encrypted, $value->getEncrypted());
    }

    /**
     * @expectedException Eventum\Crypto\CryptoException
     */
    public function testCorruptedData(): void
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        // corrupt it
        $encrypted = substr($encrypted, 1);

        $value = new EncryptedValue($encrypted);
        $value->getValue();
    }

    public function testExport(): void
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);

        $value = new EncryptedValue($encrypted);

        // export
        $exported = var_export($value, 1);
        $tmpfile = tempnam(sys_get_temp_dir(), __FUNCTION__);
        file_put_contents($tmpfile, '<?php return ' . $exported . ';');

        // load
        $loaded = require $tmpfile;

        // this should be the original plaintext
        $this->assertEquals($plaintext, (string)$loaded);

        unlink($tmpfile);
    }

    /**
     * encrypt empty string is ok
     */
    public function testEncryptEmptyString(): void
    {
        CryptoManager::encrypt('');
    }

    /**
     * should not encrypt null
     *
     * @expectedException InvalidArgumentException
     */
    public function testEncryptNull(): void
    {
        CryptoManager::encrypt(null);
    }

    /**
     * should not encrypt false
     *
     * @expectedException InvalidArgumentException
     */
    public function testEncryptFalse(): void
    {
        CryptoManager::encrypt(false);
    }

    /**
     * encrypt "0" is ok
     */
    public function testEncryptZeroString(): void
    {
        CryptoManager::encrypt('0');
    }

    /**
     * encrypt 0 is ok
     */
    public function testEncryptZero(): void
    {
        CryptoManager::encrypt(0);
    }

    /**
     * Test that the object plain text value is not visible in backtraces
     */
    public function testTraceVisibility(): void
    {
        $plaintext = 'tore';
        $encrypted = CryptoManager::encrypt($plaintext);
        $value = new EncryptedValue($encrypted);

        $f = function ($e) {
            $f2 = function ($e2) {
                $e = new Exception('boo');

                return $e->getTraceAsString();
            };

            return $f2($e);
        };

        $trace = $f($value);
        $this->assertNotContains($plaintext, $trace);
    }

    public function testZendCrypt(): void
    {
        $key = 'secretkeyvalue';
        $method = 'aes-128-cbc';
        $keysize = 16;
        $hash_function = 'sha256';
        $ENCRYPTION_INFO = 'DefusePHP|KeyForEncryption';
//        $ekey = self::HKDF($hash_function, $key, $keysize, $ENCRYPTION_INFO);
    }
}
