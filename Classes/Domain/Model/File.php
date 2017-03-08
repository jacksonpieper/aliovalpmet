<?php

namespace Schnitzler\Templavoila\Domain\Model;

/*
 * This file is part of the TemplaVoilà project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File model
 */
class File
{

    /**
     * Build a File/Folder object from an resource pointer. This might raise exceptions.
     *
     * @param $filename
     *
     * @return FileInterface|\TYPO3\CMS\Core\Resource\Folder
     */
    protected static function file($filename)
    {
        /** @var $resourceFactory ResourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        return $resourceFactory->getObjectFromCombinedIdentifier($filename);
    }

    /**
     * Retrieve filename from the FAL resource or pass the
     * given string along as this is a filename already.
     *
     * @param $filename
     *
     * @return string
     */
    public static function filename($filename)
    {
        try {
            $file = self::file($filename);
            $filename = $file->getForLocalProcessing(false);
        } catch (\Exception $e) {
        }

        return $filename;
    }

    /**
     * Check whether the given input points to an (existing) file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public static function is_file($filename)
    {
        $is_file = true;
        try {
            self::file($filename);
        } catch (\Exception $e) {
            $is_file = false;
        }

        return $is_file;
    }

    /**
     * Check whether the given file can be used for mapping
     * purposes (is an XML file).
     *
     *
     * @param string $filename
     *
     * @return bool
     */
    public static function is_xmlFile($filename)
    {
        $isXmlFile = false;
        try {
            $file = self::file($filename);
            if ($file instanceof FileInterface) {
                $isXmlFile = in_array($file->getMimeType(), ['text/html', 'application/xml'], true);
            }
        } catch (\Exception $e) {
        }

        return $isXmlFile;
    }

    /**
     * Check whether the given file can be used for mapping
     * purposes (is an XML file) based on the finfo toolset.
     *
     * @param $filename
     *
     * @return bool
     */
    protected static function is_xmlFile_finfo($filename)
    {
        $isXml = false;
        if (function_exists('finfo_open')) {
            $finfoMode = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $fi = finfo_open($finfoMode);
            $mimeInformation = @finfo_file($fi, $filename);
            if (GeneralUtility::isFirstPartOfStr($mimeInformation, 'text/html') ||
                GeneralUtility::isFirstPartOfStr($mimeInformation, 'application/xml')
            ) {
                $isXml = true;
            }
            finfo_close($fi);
        } else {
            $pi = @pathinfo($filename);
            $isXml = preg_match('/(html?|tmpl|xml)/', $pi['extension']);
        }

        return $isXml;
    }
}
