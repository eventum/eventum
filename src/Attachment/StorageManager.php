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

namespace Eventum\Attachment;

use DB_Helper;
use Eventum\Attachment\Exceptions\AttachmentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Phlib\Flysystem\Pdo\PdoAdapter;
use Setup;

/**
 * Configures and manages the Flysystem storage setup.
 */
class StorageManager
{
    /**
     * Local path where to store attachments if using filesystem storage.
     *
     * @var string
     */
    const STORAGE_PATH = APP_PATH . '/var/storage/';

    /**
     * @var MountManager
     */
    private $mount_manager;

    /**
     * The name of the default adapter. This adapter will be used to store new files.
     *
     * @var string
     */
    private $default_adapter;

    /**
     * Private constructor loads configuration from setup file.
     */
    private function __construct()
    {
        $setup = Setup::get()['attachments'];
        $this->default_adapter = $setup['default_adapter'];

        $mount_config = [
            'pdo' => $this->getPdoAdapter(),
            'legacy' => new Filesystem(new EventumLegacyAdapter()),
            'local' => new Filesystem(new Local(self::STORAGE_PATH)),
        ];

        foreach ($setup['adapters'] as $adapter_name => $adapter_config) {
            $mount_config[$adapter_name] = new Filesystem(new $adapter_config['class'](...$adapter_config['options']));
        }

        $this->mount_manager = new MountManager($mount_config);
    }

    /**
     * Create the PDO Adapter
     */
    private function getPdoAdapter()
    {
        $config = new Config([
            'table_prefix' => 'attachment',
        ]);

        return new Filesystem(new PdoAdapter(DB_Helper::getInstance()->getPdo(), $config));
    }

    /**
     * Returns the configured instance.
     *
     * @return StorageManager
     */
    public static function get()
    {
        static $manager;

        if (!$manager) {
            $manager = new self();
        }

        return $manager;
    }

    /**
     * Returns the File for the given path
     *
     * @param $path
     * @return \League\Flysystem\File
     */
    public function getFile($path)
    {
        return $this->mount_manager->get($path);
    }

    /**
     * Saves a new file
     *
     * @param $path
     * @param $contents
     * @return mixed
     */
    public function addFile($path, $contents)
    {
        if ($this->mount_manager->write($path, $contents)) {
            return $path;
        }

        throw new AttachmentException('Unable to write file');
    }

    /**
     * Renames a file. THis can only be used on the same adapter, not across different adapters
     *
     * @param $old_path
     * @param $new_path
     * @return bool
     */
    public function renameFile($old_path, $new_path)
    {
        // remove adapter from new path
        $new_path = explode('://', $new_path, 2)[1];

        return $this->mount_manager->rename($old_path, $new_path);
    }

    /**
     * Deletes the specified file
     *
     * @param $path
     * @return bool
     */
    public function deleteFile($path)
    {
        return $this->mount_manager->delete($path);
    }

    /**
     * Returns the name of the default adapter
     *
     * @return string
     */
    public function getDefaultAdapter()
    {
        return $this->default_adapter;
    }

    /**
     * @param string $old_path
     * @param string $new_path
     * @return bool
     */
    public function moveFile($old_path, $new_path)
    {
        return $this->mount_manager->move($old_path, $new_path);
    }
}
