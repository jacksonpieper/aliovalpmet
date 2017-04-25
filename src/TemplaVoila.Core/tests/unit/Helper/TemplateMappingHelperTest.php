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

namespace Schnitzler\Templavoila\Tests\Unit\Helper;

use Schnitzler\TemplaVoila\Core\Helper\TemplateMappingHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class Schnitzler\Templavoila\Tests\Unit\Helper\TemplateMappingHelperTest
 */
class TemplateMappingHelperTest extends UnitTestCase
{
    /**
     * @dataProvider testRemoveElementsThatDoNotExistInDataStructureDataProvider
     */
    public function testRemoveElementsThatDoNotExistInDataStructure($mapping, $structure, $expected)
    {
        TemplateMappingHelper::removeElementsThatDoNotExistInDataStructure($mapping, $structure);
        static::assertSame($expected, $mapping);
    }

    /**
     * @return array
     */
    public function testRemoveElementsThatDoNotExistInDataStructureDataProvider()
    {
        return [
            'test1' => [
                [
                    'ROOT' => [
                        'el' => [],
                        'MAP_EL' => ''
                    ]
                ],
                [],
                []
            ],
            'test2' => [
                [
                    'ROOT' => [
                        'el' => [
                            'field_foo' => []
                        ],
                        'MAP_EL' => ''
                    ]
                ],
                [
                    'ROOT' => []
                ],
                [
                    'ROOT' => [
                        'MAP_EL' => ''
                    ]
                ]
            ],
            'test3' => [
                [
                    'ROOT' => [
                        'el' => [
                            'field_foo' => [
                                'el' => [
                                    'field_qux' => [],
                                    'field_quux' => []
                                ],
                                'MAP_EL' => ''
                            ],
                            'field_bar' => [
                                'el' => [
                                    'field_qux' => []
                                ],
                                'MAP_EL' => ''
                            ],
                            'field_baz' => [

                            ]
                        ],
                        'MAP_EL' => ''
                    ]
                ],
                [
                    'ROOT' => [
                        'el' => [
                            'field_foo' => [
                                'el' => [
                                    'field_qux' => []
                                ]
                            ],
                            'field_bar' => [
                                'el' => []
                            ],
                            'field_qux' => [
                                'el' => []
                            ]
                        ]
                    ]
                ],
                [
                    'ROOT' => [
                        'el' => [
                            'field_foo' => [
                                'el' => [
                                    'field_qux' => []
                                ],
                                'MAP_EL' => ''
                            ],
                            'field_bar' => [
                                'MAP_EL' => ''
                            ]
                        ],
                        'MAP_EL' => ''
                    ]
                ]
            ]
        ];
    }
}
