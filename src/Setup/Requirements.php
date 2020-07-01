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

namespace Eventum\Setup;

use Eventum\Config\Paths;
use Setup;

class Requirements
{
    private const EXTENSION_MISSING_ERROR = 'The %s extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.';
    private const FILE_UPLOADS_ERROR = "The 'file_uploads' directive needs to be enabled in your PHP.INI file in order for Eventum to work properly.";

    public static function check(): void
    {
        $check = new self();
        $errors = $check->checkRequirements();
        if ($errors) {
            throw new RequirementNotSatisfiedException($errors);
        }
    }

    private function checkRequirements(): array
    {
        $errors = [];

        // sync with composer.json
        $requiredExtensions = [
            'ctype',
            'dom',
            'fileinfo',
            'filter',
            'gd',
            'gettext',
            'iconv',
            'intl',
            'json',
            'mbstring',
            'pcre',
            'pdo',
            'pdo_mysql',
            'session',
            'spl',
            'xml',
        ];

        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $errors[] = sprintf(self::EXTENSION_MISSING_ERROR, $extension);
            }
        }

        // check for the file_uploads php.ini directive
        if (ini_get('file_uploads') != '1') {
            $errors[] = self::FILE_UPLOADS_ERROR;
        }

        $configPath = Setup::getConfigPath();
        $setupFile = Setup::getSetupFile();

        $error = $this->checkPermissions($configPath, "Directory '" . $configPath . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions($setupFile, "File '" . $setupFile . "'");
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(
            $configPath . '/private_key.php',
            "File '" . $configPath . '/private_key.php' . "'"
        );
        if (!empty($error)) {
            $errors[] = $error;
        }
        if (!empty($error)) {
            $errors[] = $error;
        }

        $error = $this->checkPermissions(Paths::APP_LOCKS_PATH, "Directory '" . Paths::APP_LOCKS_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(Paths::APP_LOG_PATH, "Directory '" . Paths::APP_LOG_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(Paths::APP_TPL_COMPILE_PATH, "Directory '" . Paths::APP_TPL_COMPILE_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }

        return $errors;
    }

    /**
     * Checks for $file for write permission.
     *
     * IMPORTANT: if the file does not exist, an empty file is created.
     */
    private function checkPermissions($file, $desc, $is_directory = false): string
    {
        clearstatcache();
        if (!file_exists($file)) {
            if (!$is_directory) {
                // try to create the file ourselves then
                $fp = @fopen($file, 'wb');
                if (!$fp) {
                    return $this->getPermissionError($file, $is_directory, false);
                }
                @fclose($fp);
            } else {
                if (!mkdir($file) && !is_dir($file)) {
                    return $this->getPermissionError($file, $is_directory, false);
                }
            }
        }
        clearstatcache();
        if (!is_writable($file)) {
            if (stripos(PHP_OS, 'win') === false) {
                // let's try to change the permissions ourselves
                @chmod($file, 0644);
                clearstatcache();
                if (!is_writable($file)) {
                    return $this->getPermissionError($file, $is_directory, true);
                }
            } else {
                return $this->getPermissionError($file, $is_directory, true);
            }
        }
        if (stripos(PHP_OS, 'win') !== false) {
            // need to check whether we can really create files in this directory or not
            // since is_writable() is not trustworthy on windows platforms
            if (is_dir($file)) {
                $fp = @fopen($file . '/dummy.txt', 'wb');
                if (!$fp) {
                    return "$desc is not writable";
                }
                @fwrite($fp, 'test');
                @fclose($fp);
                // clean up after ourselves
                @unlink($file . '/dummy.txt');
            }
        }

        return '';
    }

    private function getPermissionError(string $path, bool $is_directory, bool $exists): string
    {
        if ($is_directory) {
            $title = 'Directory';
        } else {
            $title = 'File';
        }
        $error = "$title <b>'" . $path . ($is_directory ? '/' : '') . "'</b> ";

        if (!$exists) {
            $error .= "does not exist. Please create the $title and reload this page.";
        } else {
            $error .= "is not writeable. Please change this $title to be writeable by the web server.";
        }

        return $error;
    }
}
