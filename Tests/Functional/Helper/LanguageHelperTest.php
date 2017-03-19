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

namespace Schnitzler\Templavoila\Tests\Functional\Service;

use Schnitzler\Templavoila\Helper\LanguageHelper;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Tests\Functional\Service\LanguageHelperTest
 */
class LanguageHelperTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/version',
        'typo3/sysext/workspaces',
        'typo3conf/ext/templavoila'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->backendUserFixture = GeneralUtility::getFileAbsFileName('EXT:templavoila/Tests/Functional/Helper/LanguageHelperTestFixtures/be_users.xml');

        $fixtureTables = [
            'pages',
            'pages_language_overlay',
            'sys_language'
        ];

        $fixtureRootPath = ORIGINAL_ROOT . 'typo3conf/ext/templavoila/Tests/Functional/Helper/LanguageHelperTestFixtures/';

        foreach ($fixtureTables as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        Bootstrap::getInstance()->initializeLanguageObject();
    }

    public function testGetAllWithoutTypoScriptConfig()
    {
        $this->setUpBackendUserFromFixture(1);

        $expected = [
            -1 => [
                'uid' => -1,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'All',
                'flag' => 'multiple',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => 0,
                'sorting' => -1,
                'flagIconIdentifier' => 'flags-multiple'
            ],
            0 => [
                'uid' => 0,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'Default',
                'flag' => '',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => ''
            ],
            1 => [
                'uid' => 1,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'German',
                'flag' => 'de',
                'language_isocode' => 'de',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-de'
            ],
            2 => [
                'uid' => 2,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'French',
                'flag' => 'fr',
                'language_isocode' => 'fr',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-fr'
            ],
            3 => [
                'uid' => 3,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'Chinese',
                'flag' => 'cn',
                'language_isocode' => 'zh',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-cn'
            ]
        ];

        static::assertSame($expected, LanguageHelper::getAll(0));
    }

    public function testGetAllWithDefaultLanguageTypoScriptConfig()
    {
        $this->setUpBackendUserFromFixture(1);

        $expected = [
            -1 => [
                'uid' => -1,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'All',
                'flag' => 'multiple',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => 0,
                'sorting' => -1,
                'flagIconIdentifier' => 'flags-multiple'
            ],
            0 => [
                'uid' => 0,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'English',
                'flag' => 'gb',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-gb'
            ],
            1 => [
                'uid' => 1,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'German',
                'flag' => 'de',
                'language_isocode' => 'de',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-de'
            ],
            2 => [
                'uid' => 2,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'French',
                'flag' => 'fr',
                'language_isocode' => 'fr',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-fr'
            ],
            3 => [
                'uid' => 3,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'Chinese',
                'flag' => 'cn',
                'language_isocode' => 'zh',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-cn'
            ]
        ];

        static::assertSame($expected, LanguageHelper::getAll(1));
    }

    public function testGetAllWithDisabledLanguagesTypoScriptConfig()
    {
        $this->setUpBackendUserFromFixture(1);

        $expected = [
            -1 => [
                'uid' => -1,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'All',
                'flag' => 'multiple',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => 0,
                'sorting' => -1,
                'flagIconIdentifier' => 'flags-multiple'
            ],
            0 => [
                'uid' => 0,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'Default',
                'flag' => '',
                'language_isocode' => 'DEF',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => ''
            ],
            1 => [
                'uid' => 1,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'German',
                'flag' => 'de',
                'language_isocode' => 'de',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-de'
            ],
            3 => [
                'uid' => 3,
                'pid' => 0,
                'tstamp' => 0,
                'hidden' => 0,
                'title' => 'Chinese',
                'flag' => 'cn',
                'language_isocode' => 'zh',
                'static_lang_isocode' => 0,
                'sorting' => 0,
                'flagIconIdentifier' => 'flags-cn'
            ]
        ];

        static::assertSame($expected, LanguageHelper::getAll(2));
    }

    public function testGetLanguageIsoCode()
    {
        $this->setUpBackendUserFromFixture(1);

        static::assertSame('DEF', LanguageHelper::getLanguageIsoCode(0, -1));
        static::assertSame('DEF', LanguageHelper::getLanguageIsoCode(0, 0));
        static::assertSame('DE', LanguageHelper::getLanguageIsoCode(0, 1, true));
        static::assertSame('FR', LanguageHelper::getLanguageIsoCode(0, 2, true));
        static::assertSame('ZH', LanguageHelper::getLanguageIsoCode(0, 3, true));
        static::assertSame('', LanguageHelper::getLanguageIsoCode(0, 4, true)); // non existing language
    }

    public function testGetLanguageTitle()
    {
        $this->setUpBackendUserFromFixture(1);

        static::assertSame('All', LanguageHelper::getLanguageTitle(0, -1));
        static::assertSame('Default', LanguageHelper::getLanguageTitle(0, 0));
        static::assertSame('German', LanguageHelper::getLanguageTitle(0, 1));
        static::assertSame('French', LanguageHelper::getLanguageTitle(0, 2));
        static::assertSame('Chinese', LanguageHelper::getLanguageTitle(0, 3));
        static::assertSame('Undefined', LanguageHelper::getLanguageTitle(0, 4)); // non existing language
    }

    public function testGetLanguageFlagIconIdentifier()
    {
        $this->setUpBackendUserFromFixture(1);

        static::assertSame('flags-multiple', LanguageHelper::getLanguageFlagIconIdentifier(0, -1));
        static::assertSame('', LanguageHelper::getLanguageFlagIconIdentifier(0, 0));
        static::assertSame('flags-de', LanguageHelper::getLanguageFlagIconIdentifier(0, 1));
        static::assertSame('flags-fr', LanguageHelper::getLanguageFlagIconIdentifier(0, 2));
        static::assertSame('flags-cn', LanguageHelper::getLanguageFlagIconIdentifier(0, 3));
        static::assertSame('', LanguageHelper::getLanguageFlagIconIdentifier(0, 4)); // non existing language
    }

    public function testGetPageLanguages()
    {
        $this->setUpBackendUserFromFixture(1);

        static::assertSame(
            [
                0 => [
                    'uid' => 0,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'English',
                    'flag' => 'gb',
                    'language_isocode' => 'DEF',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-gb'
                ],
                1 => [
                    'uid' => 1,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'German',
                    'flag' => 'de',
                    'language_isocode' => 'de',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-de'
                ]
            ],
            LanguageHelper::getPageLanguages(1)
        );
        static::assertSame(
            [
                0 => [
                    'uid' => 0,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'Default',
                    'flag' => '',
                    'language_isocode' => 'DEF',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => ''
                ],
            ],
            LanguageHelper::getPageLanguages(2)
        );
    }

    public function testHasPageTranslations()
    {
        $this->setUpBackendUserFromFixture(1);

        static::assertTrue(LanguageHelper::hasPageTranslations(1));
        static::assertFalse(LanguageHelper::hasPageTranslations(2));
    }

    public function testGetNonExistingPageOverlayLanguages()
    {
        $this->setUpBackendUserFromFixture(1);

        static::assertSame(
            [
                2 => [
                    'uid' => 2,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'French',
                    'flag' => 'fr',
                    'language_isocode' => 'fr',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-fr'
                ],
                3 => [
                    'uid' => 3,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'Chinese',
                    'flag' => 'cn',
                    'language_isocode' => 'zh',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-cn'
                ]
            ],
            LanguageHelper::getNonExistingPageOverlayLanguages(1)
        );

        static::assertSame(
            [
                1 => [
                    'uid' => 1,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'German',
                    'flag' => 'de',
                    'language_isocode' => 'de',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-de'
                ],
                3 => [
                    'uid' => 3,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'Chinese',
                    'flag' => 'cn',
                    'language_isocode' => 'zh',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-cn'
                ]
            ],
            LanguageHelper::getNonExistingPageOverlayLanguages(2)
        );

        static::assertSame(
            [
                1 => [
                    'uid' => 1,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'German',
                    'flag' => 'de',
                    'language_isocode' => 'de',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-de'
                ],
                3 => [
                    'uid' => 3,
                    'pid' => 0,
                    'tstamp' => 0,
                    'hidden' => 0,
                    'title' => 'Chinese',
                    'flag' => 'cn',
                    'language_isocode' => 'zh',
                    'static_lang_isocode' => 0,
                    'sorting' => 0,
                    'flagIconIdentifier' => 'flags-cn'
                ]
            ],
            LanguageHelper::getNonExistingPageOverlayLanguages(3)
        );
    }
}
