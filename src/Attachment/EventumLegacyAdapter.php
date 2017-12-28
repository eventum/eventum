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
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\NotSupportedException;

class EventumLegacyAdapter implements AdapterInterface
{
    /**
     * @param string $path
     * @return array
     */
    private function getAttachment($path)
    {
        $sql = 'SELECT
                    iaf_id,
                    iaf_file,
                    iaf_filename,
                    iaf_filetype,
                    iaf_filesize,
                    iaf_created_date,
                    iap_flysystem_path,
                    iaf_iat_id
                 FROM
                    `issue_attachment_file`,
                    `issue_attachment_file_path`
                 WHERE
                    iap_iaf_id = iaf_id AND
                    iaf_id=?';
        $res = DB_Helper::getInstance()->getRow($sql, [$path]);

        return $res;
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     * @return array|bool
     */
    public function has($path)
    {
        if ($this->getAttachment($path)) {
            return true;
        }

        return false;
    }

    /**
     * Read a file.
     *
     * @param string $path
     * @return array
     */
    public function read($path)
    {
        return [
            'type' => 'file',
            'path' => $path,
            'contents' => $this->getAttachment($path)['iaf_file'],
        ];
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     * @return array
     */
    public function readStream($path)
    {
        $data = $this->read($path);

        $data['stream'] = fopen('php://temp', 'r+');
        fwrite($data['stream'], $data['contents']);
        rewind($data['stream']);

        unset($data['contents']);

        return $data;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     * @return array
     */
    public function getMetadata($path)
    {
        $attachment = $this->getAttachment($path);

        $data = [
            'type' => 'file',
            'path' => $path,
            'size' => $attachment['iaf_filesize'],
            'mimetype' => $attachment['iaf_filetype'],
            'timestamp' => $attachment['iaf_created_date'], // TODO: Change to timestamp?
            'visibility' => self::VISIBILITY_PRIVATE, // Not actually used
        ];

        return $data;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     * @return array
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     * @return array
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     * @return array
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     * @return array
     */
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        throw new NotSupportedException('Writing is not supported for legacy adapter');
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        throw new NotSupportedException('Writing is not supported for legacy adapter');
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        throw new NotSupportedException('Writing is not supported for legacy adapter');
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        throw new NotSupportedException('Writing is not supported for legacy adapter');
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function rename($path, $newpath)
    {
        throw new NotSupportedException('Rename is not supported for legacy adapter');
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function copy($path, $newpath)
    {
        throw new NotSupportedException('Copying is not supported for legacy adapter');
    }

    /**
     * Delete a file.
     *
     * @param string $path
     * @return bool
     */
    public function delete($path)
    {
        $sql = 'UPDATE
                    `issue_attachment_file`
                SET
                    iaf_file = NULL
                WHERE
                    iaf_id=?';

        DB_Helper::getInstance()->query($sql, [$path]);

        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     * @return bool
     */
    public function deleteDir($dirname)
    {
        // nothing to do here.
        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        throw new NotSupportedException('Writing is not supported for legacy adapter');
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        throw new NotSupportedException('Changing visibility is not supported for legacy adapter');
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        throw new NotSupportedException('Listing Contents is not supported for legacy adapter');
    }
}
