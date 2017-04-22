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

namespace Schnitzler\TemplaVoila\Controller\Tests\Unit;

use Psr\Log\NullLogger;
use Schnitzler\TemplaVoila\Controller\FrontendController;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class Schnitzler\TemplaVoila\Controller\FrontendControllerTest
 */
class FrontendControllerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider resolveLanguageKeyProvider
     *
     * @param array $data
     * @param string $expected
     */
    public function resolveLanguageKey($data, $expected)
    {
        list(
            $languageUid,
            $languageIsoCode,
            $langDisable,
            $langChildren
            ) = array_values($data);

        $frontendController = new \stdClass();
        $frontendController->sys_language_uid = $languageUid;
        $frontendController->sys_language_isocode = $languageIsoCode;

        $logger = new NullLogger();

        /** @var $mockObject FrontendController | \PHPUnit_Framework_MockObject_MockObject | AccessibleObjectInterface  */
        $mockObject = $this->getAccessibleMock(FrontendController::class, ['getLogger'], [], '', false);
        $mockObject->_set('frontendController', $frontendController);
        $mockObject->expects($this->any())->method('getLogger')->willReturn($logger);

        $this->assertSame($expected, $mockObject->_call('resolveLanguageKey', $langDisable, $langChildren));
    }

    /**
     * @return array
     */
    public function resolveLanguageKeyProvider()
    {
        return [
            [
                [
                    1,
                    'en',
                    false,
                    true // trigger
                ],
                'lDEF'
            ],
            [
                [
                    1,
                    'en',
                    true, // trigger
                    false
                ],
                'lDEF'
            ],
            [
            [
                0, // trigger
                'en',
                false,
                false
            ],
                'lDEF'
            ],
            [
                [
                    1,
                    '', // trigger
                    false,
                    false
                ],
                'lDEF'
            ],
            [
                [
                    1,
                    'de',
                    false,
                    false
                ],
                'lDE'
            ],
            [
                [
                    1,
                    'EN',
                    false,
                    false
                ],
                'lEN'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider inheritValueDataProvider
     *
     * @param array $data
     * @param string $expected
     */
    public function inheritValueResultsWithParamMatrix($data, $expected)
    {
        $logger = new NullLogger();

        /** @var $mockObject FrontendController | \PHPUnit_Framework_MockObject_MockObject | AccessibleObjectInterface  */
        $mockObject = $this->getAccessibleMock(FrontendController::class, ['getLogger'], [], '', false);
        $mockObject->initVars(['dontInheritValueFromDefault' => false]);
        $mockObject->expects($this->any())->method('getLogger')->willReturn($logger);

        list($dataValues, $valueKey, $overlayMode) = array_values($data);

        $this->assertSame($expected, $mockObject->_call('inheritValue', $dataValues, $valueKey, $overlayMode));
    }

    /**
     * @return array
     */
    public function inheritValueDataProvider()
    {
        return [
            [
                [
                    ['vDEF' => 'en', 'foo' => 'bar'],
                    'foo',
                ],
                'bar',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 'bar'],
                    'vDEF',
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => '1', 'vFR' => '2'],
                    'vFR',
                ],
                '2',
            ],
            [
                [
                    ['vDEF' => '1'],
                    'vFR',
                ],
                '1',
            ],
            [
                [
                    [],
                    'vFR',
                ],
                '',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => ''],
                    'foo',
                    'ifFalse'
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => '0'],
                    'foo',
                    'ifFalse'
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 0],
                    'foo',
                    'ifFalse'
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => false],
                    'foo',
                    'ifFalse'
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 'bar'],
                    'foo',
                    'ifFalse'
                ],
                'bar',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => ''],
                    'foo',
                    'ifBlank'
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => false],
                    'foo',
                    'ifBlank'
                ],
                'en',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => '0'],
                    'foo',
                    'ifBlank'
                ],
                '0',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 0],
                    'foo',
                    'ifBlank'
                ],
                '0',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 'bar'],
                    'foo',
                    'never'
                ],
                'bar',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 'bar'],
                    'foo',
                    'removeIfBlank'
                ],
                '',
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => ''],
                    'foo',
                    'removeIfBlank'
                ],
                ['ERROR' => '__REMOVE'],
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => 'bar'],
                    'foo',
                    ''
                ],
                'bar'
            ],
            [
                [
                    ['vDEF' => 'en', 'foo' => ''],
                    'foo',
                    ''
                ],
                'en'
            ],
        ];
    }
}
