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

namespace Schnitzler\TemplaVoila\Controller\Tests\Functional;

use Schnitzler\TemplaVoila\Controller\FrontendController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class Schnitzler\TemplaVoila\Controller\FrontendControllerTest
 */
class FrontendControllerTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/version',
        'typo3/sysext/workspaces',
        'typo3conf/ext/templavoila'
    ];

    /**
     * @var FrontendController | AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendController;

    public function setUp()
    {
        parent::setUp();

        $fixtureRootPath = ExtensionManagementUtility::extPath('templavoila', 'src/TemplaVoila.Controller/tests/functional/FrontendControllerTestFixtures/');

        $this->frontendController = $this->getAccessibleMock(FrontendController::class, ['getLogger'], [], '', false);

        $fixtureTables = [
            'tx_templavoila_tmplobj'
        ];

        foreach ($fixtureTables as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        Bootstrap::getInstance()->initializeLanguageObject();

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        /** @var TypoScriptFrontendController $TSFE */
        $TSFE = GeneralUtility::makeInstance(TypoScriptFrontendController::class, [], 0, 0);
        $TSFE->sys_page = $pageRepository;

        $GLOBALS['TSFE'] = $TSFE;
    }

    /**
     * @dataProvider testGetTemplateRecordDataProvider
     */
    public function testGetTemplateRecord(array $data, $expectedUid)
    {
        list($uid, $renderType, $sysLanguageUid) = array_values($data);

        $record = $this->frontendController->_call('getTemplateRecord', $uid, $renderType, $sysLanguageUid);

        static::assertTrue(is_array($record));
        static::assertSame($expectedUid, $record['uid']);
        static::assertSame((string)$renderType, $record['rendertype']);
        static::assertSame($sysLanguageUid, $record['sys_language_uid']);
    }

    public function testGetTemplateRecordDataProvider()
    {
        return [
            'fetchOriginalRecordWithoutRenderTypeWithoutSysLanguageUid' => [
                [
                    1,
                    '',
                    0
                ],
                1
            ],
            'fetchChildRecordWithoutRenderTypeWithSysLanguageUid' => [
                [
                    10,
                    '',
                    1
                ],
                11
            ],
            'fetchChildRecordViaReferenceRecordWithoutRenderTypeWithSysLanguageUid' => [
                [
                    20,
                    '',
                    1
                ],
                22
            ],
            'fetchChildRecordWithRenderTypeWithoutSysLanguageUid' => [
                [
                    30,
                    'print',
                    0
                ],
                31
            ],
            'fetchChildRecordViaReferenceRecordWithRenderTypeWithoutSysLanguageUid' => [
                [
                    40,
                    'print',
                    0
                ],
                42
            ],
            'fetchChildRecordWithRenderTypeWithSysLanguageUid' => [
                [
                    50,
                    'print',
                    1
                ],
                51
            ],
            'fetchChildRecordViaReferenceRecordWithRenderTypeWithSysLanguageUid' => [
                [
                    60,
                    'print',
                    1
                ],
                62
            ]
        ];
    }
}
