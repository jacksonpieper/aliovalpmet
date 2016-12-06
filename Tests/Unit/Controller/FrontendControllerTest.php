<?php

namespace Schnitzler\Templavoila\Tests\Unit\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use Schnitzler\Templavoila\Controller\FrontendController;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @author Alexander Schnitzler <typo3@alexanderschnitzler.de>
 */
class FrontendControllerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider inheritValueDataProvider
     *
     * @param array $data
     * @param string $expected
     */
    public function inheritValueResultsWithParamMatrix($data, $expected)
    {
        /** @var $mockObject FrontendController | \PHPUnit_Framework_MockObject_MockObject | AccessibleObjectInterface  */
        $mockObject = $this->getAccessibleMock(FrontendController::class, ['log'], [], '', false);
        $mockObject->initVars(['dontInheritValueFromDefault' => false]);

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
