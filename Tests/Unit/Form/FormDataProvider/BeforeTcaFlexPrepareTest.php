<?php

namespace Schnitzler\Templavoila\Tests\Unit\Form\FormDataProvider;

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
use Schnitzler\Templavoila\Form\FormDataProvider\BeforeTcaFlexPrepare;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class Schnitzler\Templavoila\Tests\Unit\Controller\FrontendControllerTest
 */
class BeforeTcaFlexPrepareTest extends UnitTestCase
{

    public function testRemoveSuperfluousTemplaVoilaNodesRecursive()
    {
        $data = [
            'ROOT' => [
                'tx_templavoila' => [
                    'title' => 'ROOT'
                ],
                'type' => 'array',
                'el' => [
                    'container' => [
                        'tx_templavoila' => [
                            'title' => 'container'
                        ],
                        'type' => 'array',
                        'el' => [
                            'child1' => [
                                'tx_templavoila' => [
                                    'title' => 'child1'
                                ],
                                'TCEforms' => [
                                    'type' => 'input'
                                ]
                            ],
                            'child2' => [
                                'tx_templavoila' => [
                                    'title' => 'child2'
                                ],
                                'TCEforms' => [
                                    'type' => 'input'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $expected = [
            'ROOT' => [
                'tx_templavoila' => [
                    'title' => 'ROOT'
                ],
                'type' => 'array',
                'el' => [
                    'container' => [
                        'type' => 'array',
                        'el' => [
                            'child1' => [
                                'TCEforms' => [
                                    'type' => 'input'
                                ]
                            ],
                            'child2' => [
                                'TCEforms' => [
                                    'type' => 'input'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $object = new BeforeTcaFlexPrepare();
        $data = $object->removeSuperfluousTemplaVoilaNodesRecursive($data);

        static::assertSame($expected, $data);
    }
}
