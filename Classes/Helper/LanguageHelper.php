<?php

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

namespace Schnitzler\Templavoila\Helper;

use Schnitzler\Templavoila\Domain\Repository\SysLanguageRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Helper\LanguageHelper
 */
final class LanguageHelper
{

    /**
     * @var array
     */
    private static $languages;

    /**
     * @var array
     */
    private static $pageLanguages = [];

    /**
     * @var array
     */
    private static $disabledLanguages = [];

    /**
     * @var array
     */
    private static $defaultLanguageLabel = [];

    /**
     * @var array
     */
    private static $defaultLanguageIconIdentifier = [];

    /**
     * @var array
     */
    private static $nonExistingPageOverlayLanguages = [];

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // deliberately private
    }

    /**
     * @param int $pageId
     * @return array
     */
    public static function getAll($pageId)
    {
        if (!is_array(static::$languages)) {
            $languages = [];

            $repository = GeneralUtility::makeInstance(SysLanguageRepository::class);
            $sysLanguageRecords = $repository->findAll();

            $languages[-1] = [
                'uid' => '-1',
                'pid' => '0',
                'tstamp' => '0',
                'hidden' => '0',
                'title' => 'All',
                'flag' => 'multiple',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => '0',
                'flagIconIdentifier' => 'flags-multiple'
            ];

            $languages = static::addDefaultLanguageEntry($languages, $pageId);

            $disableLanguages = static::getDisabledLanguages($pageId);
            foreach ($sysLanguageRecords as $sysLanguageRecord) {
                BackendUtility::workspaceOL('sys_language', $sysLanguageRecord);
                $uid = (int)$sysLanguageRecord['uid'];

                if (in_array($uid, $disableLanguages, true)) {
                    continue;
                }

                $languages[$uid] = $sysLanguageRecord;
                $languages[$uid]['flagIconIdentifier'] = '';
                if ($sysLanguageRecord['flag'] !== '') {
                    $languages[$uid]['flagIconIdentifier'] = 'flags-' . $sysLanguageRecord['flag'];
                }
            }

            static::$languages = $languages;
        }

        return static::$languages;
    }

    /**
     * @param $int pageId
     * @return array
     */
    public static function getNonExistingPageOverlayLanguages($pageId)
    {
        if (!isset(static::$nonExistingPageOverlayLanguages[$pageId])) {
            $nonExistingPageOverlayLanguages = [];

            $repository = GeneralUtility::makeInstance(SysLanguageRepository::class);
            $sysLanguageRecords = $repository->findAllForPossiblePageTranslations($pageId);

            if (count($sysLanguageRecords) > 0) {
                $disableLanguages = static::getDisabledLanguages($pageId);

                foreach ($sysLanguageRecords as $sysLanguageRecord) {
                    BackendUtility::workspaceOL('sys_language', $sysLanguageRecord);
                    $uid = (int)$sysLanguageRecord['uid'];

                    if (in_array($uid, $disableLanguages, true)) {
                        continue;
                    }

                    $nonExistingPageOverlayLanguages[$uid] = $sysLanguageRecord;
                    $nonExistingPageOverlayLanguages[$uid]['flagIconIdentifier'] = '';
                    if ($sysLanguageRecord['flag'] !== '') {
                        $nonExistingPageOverlayLanguages[$uid]['flagIconIdentifier'] = 'flags-' . $sysLanguageRecord['flag'];
                    }
                }

                static::$nonExistingPageOverlayLanguages[$pageId] = $nonExistingPageOverlayLanguages;
            }
        }

        return static::$nonExistingPageOverlayLanguages[$pageId];
    }

    /**
     * @param $int pageId
     * @return array
     */
    public static function getPageLanguages($pageId)
    {
        if (!isset(static::$pageLanguages[$pageId])) {
            $repository = GeneralUtility::makeInstance(SysLanguageRepository::class);
            $pageLanguages = static::addDefaultLanguageEntry([], $pageId);

            $sysLanguageRecords = $repository->findAllForPid($pageId);
            if (count($sysLanguageRecords) > 0) {
                $disableLanguages = static::getDisabledLanguages($pageId);

                foreach ($sysLanguageRecords as $sysLanguageRecord) {
                    BackendUtility::workspaceOL('sys_language', $sysLanguageRecord);
                    $uid = (int)$sysLanguageRecord['uid'];

                    if (in_array($uid, $disableLanguages, true)) {
                        // todo: not sure if this case is actually relevant
                        continue;
                    }

                    $pageLanguages[$uid] = $sysLanguageRecord;
                    $pageLanguages[$uid]['flagIconIdentifier'] = '';
                    if ($sysLanguageRecord['flag'] !== '') {
                        $pageLanguages[$uid]['flagIconIdentifier'] = 'flags-' . $sysLanguageRecord['flag'];
                    }
                }
            }

            static::$pageLanguages[$pageId] = $pageLanguages;
        }

        return static::$pageLanguages[$pageId];
    }

    /**
     * @param int $pageId
     * @return bool
     */
    public static function hasPageTranslations($pageId)
    {
        return count(static::getPageLanguages($pageId)) > 1;
    }

    /**
     * @param int $pageId
     * @param int $languageId
     * @return string
     */
    public static function getLanguageTitle($pageId, $languageId)
    {
        $languages = static::getAll($pageId);

        if (isset($languages[$languageId]['title'])) {
            return $languages[$languageId]['title'];
        }

        return 'Undefined';
    }

    /**
     * @param int $pageId
     * @param int $languageId
     * @return string
     */
    public static function getLanguageFlagIconIdentifier($pageId, $languageId)
    {
        $languages = static::getAll($pageId);

        if (isset($languages[$languageId]['flagIconIdentifier'])) {
            return $languages[$languageId]['flagIconIdentifier'];
        }

        return '';
    }

    /**
     * @param int $pageId
     * @param int $languageId
     * @param bool $uppercase
     * @return string
     */
    public static function getLanguageIsoCode($pageId, $languageId, $uppercase = false)
    {
        $languages = static::getAll($pageId);

        if (isset($languages[$languageId]['language_isocode'])) {
            return $uppercase
                ? strtoupper($languages[$languageId]['language_isocode'])
                : $languages[$languageId]['language_isocode'];
        }

        return '';
    }

    /**
     * @param int $id
     * @return int[]
     */
    protected static function getDisabledLanguages($id)
    {
        if (!isset(static::$disabledLanguages[$id])) {
            $ts = BackendUtility::getModTSconfig($id, 'mod.SHARED');

            static::$disabledLanguages[$id] = [];
            if (isset($ts['properties']['disableLanguages'])) {
                static::$disabledLanguages[$id] = array_map(
                    function ($language) {
                        return (int) $language;
                    },
                    GeneralUtility::trimExplode(
                        ',',
                        $ts['properties']['disableLanguages'],
                        true
                    )
                );
            }
        }

        return static::$disabledLanguages[$id];
    }

    /**
     * @param int $id
     * @return string
     */
    protected static function getDefaultLanguageLabel($id)
    {
        if (!isset(static::$defaultLanguageLabel[$id])) {
            $ts = BackendUtility::getModTSconfig($id, 'mod.SHARED');

            static::$defaultLanguageLabel[$id] = 'Default';
            if (isset($ts['properties']['defaultLanguageLabel'])) {
                static::$defaultLanguageLabel[$id] = $ts['properties']['defaultLanguageLabel'];
            }
        }

        return static::$defaultLanguageLabel[$id];
    }

    /**
     * @param int $id
     * @return string
     */
    protected static function getDefaultLanguageIconIdentifier($id)
    {
        if (!isset(static::$defaultLanguageIconIdentifier[$id])) {
            $ts = BackendUtility::getModTSconfig($id, 'mod.SHARED');

            static::$defaultLanguageIconIdentifier[$id] = '';
            if (isset($ts['properties']['defaultLanguageFlag'])) {
                static::$defaultLanguageIconIdentifier[$id] = $ts['properties']['defaultLanguageFlag'];
            }
        }

        return static::$defaultLanguageIconIdentifier[$id];
    }

    /**
     * @param array $languages
     * @param int $pageId
     * @return array
     */
    private static function addDefaultLanguageEntry(array $languages, $pageId)
    {
        $languages[0] = [
            'uid' => '0',
            'pid' => '0',
            'tstamp' => '0',
            'hidden' => '0',
            'title' => static::getDefaultLanguageLabel($pageId),
            'flag' => '',
            'language_isocode' => 'DEF',
            'static_lang_isocode' => '0',
            'flagIconIdentifier' => ''
        ];

        $defaultLanguageIconIdentifier = static::getDefaultLanguageIconIdentifier($pageId);
        if ($defaultLanguageIconIdentifier !== '') {
            $languages[0]['flag'] = $defaultLanguageIconIdentifier;
            $languages[0]['flagIconIdentifier'] = 'flags-' . $defaultLanguageIconIdentifier;
        }

        return $languages;
    }
}
