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

namespace Schnitzler\TemplaVoila\Data\Tests\Unit\Domain\Model;

use Schnitzler\TemplaVoila\Data\Domain\Model\HtmlMarkup;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class Schnitzler\Templavoila\Tests\Unit\Controller\HtmlMarkupTest
 */
class HtmlMarkupTest extends UnitTestCase
{

    /**
     * @return array
     */
    public function testSplitPathDataProvider()
    {
        return [
            [
                '',
                []
            ],
            [
                'foo|bar',
                [
                    0 => [
                        'fullpath' => 'foo',
                        'tagList' => 'foo',
                        'path' => 'foo',
                        'el' => 'foo',
                        'parent' => ''
                    ],
                    1 => [
                        'fullpath' => 'bar',
                        'tagList' => 'bar',
                        'path' => 'bar',
                        'el' => 'bar',
                        'parent' => ''
                    ]
                ]
            ],
            [
                'body',
                [
                    0 => [
                        'fullpath' => 'body',
                        'tagList' => 'body',
                        'path' => 'body',
                        'el' => 'body',
                        'parent' => ''
                    ]
                ]
            ],
            [
                'body/INNER',
                [
                    0 => [
                        'fullpath' => 'body/INNER',
                        'modifier' => 'INNER',
                        'modifier_value' => null,
                        'modifier_lu' => '/INNER',
                        'tagList' => 'body',
                        'path' => 'body',
                        'el' => 'body',
                        'parent' => ''
                    ]
                ]
            ],
            [
                'span.class[1]/ATTR:class',
                [
                    0 => [
                        'fullpath' => 'span.class[1]/ATTR:class',
                        'modifier' => 'ATTR',
                        'modifier_value' => 'class',
                        'modifier_lu' => '',
                        'tagList' => 'span',
                        'path' => 'span.class[1]',
                        'el' => 'span',
                        'parent' => ''
                    ]
                ]
            ],
            [
                'aside#search-2 form.search-form[1] label[1] span.screen-reader-text[1]/RANGE:input.search-field[1]',
                [
                    0 => [
                        'fullpath' => 'aside#search-2 form.search-form[1] label[1] span.screen-reader-text[1]/RANGE:input.search-field[1]',
                        'modifier' => 'RANGE',
                        'modifier_value' => 'input.search-field[1]',
                        'modifier_lu' => '/RANGE:input.search-field[1]',
                        'tagList' => 'aside,form,label,span,input',
                        'path' => 'aside#search-2 form.search-form[1] label[1] span.screen-reader-text[1]',
                        'el' => 'span',
                        'parent' => 'aside#search-2 form.search-form[1] label[1]'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider testSplitPathDataProvider
     */
    public function testSplitPath($path, $expected)
    {
        /** @var AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject | HtmlMarkup $htmlMarkup */
        $htmlMarkup = $this->getAccessibleMock(HtmlMarkup::class, ['dummy']);

        static::assertSame($expected, $htmlMarkup->_call('splitPath', $path));
    }

    public function testSplitTagTypes()
    {
        /** @var AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject | HtmlMarkup $htmlMarkup */
        $htmlMarkup = $this->getAccessibleMock(HtmlMarkup::class, ['dummy']);

        $tags = 'hr,body,footer,br,article,aside,div,optgroup';

        /**
         * Method returns an array with 2 comma separated lists
         * of alphabetically ordered tags.
         *
         * The first list contains all tags that needs a closing tag,
         * the second list contains all tags that can stand alone.
         */
        $expected = ['article,aside,body,div,footer,optgroup', 'br,hr'];

        static::assertSame($expected, $htmlMarkup->_call('splitTagTypes', $tags));
    }

    /**
     * @return array
     */
    public function testMakePathDataProvider()
    {
        return [
            [
                [
                    '',
                    '',
                    []
                ],
                '[1]'
            ],
            [
                [
                    '',
                    'body',
                    [
                        'id' => 'id'
                    ]
                ],
                'body#id'
            ],
            [
                [
                    '',
                    'body',
                    [
                        'class' => 'class'
                    ]
                ],
                'body.class[1]'
            ]
        ];
    }

    /**
     * @param $path
     * @param $firstTagName
     * @param $attr
     *
     * @dataProvider testMakePathDataProvider
     */
    public function testMakePath($params, $expected)
    {
        /** @var AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject | HtmlMarkup $htmlMarkup */
        $htmlMarkup = $this->getAccessibleMock(HtmlMarkup::class, ['dummy']);

        list($path, $firstTagName, $attr) = array_values($params);

        static::assertSame($expected, $htmlMarkup->_call('makePath', $path, $firstTagName, $attr));
    }

    /**
     * @return array
     */
    public function testMergeSearchpartsIntoContentDataProvider()
    {
        return [
            [
                [
                    '',
                    [],
                    ''
                ],
                ''
            ],
            [
                [
                    '<div><!--###654b191b017dd268652de67882ae540e###--></div>',
                    [
                        'body.home~~~blog[1]' => [
                            'placeholder' => '<!--###654b191b017dd268652de67882ae540e###-->',
                            'content' => '<div>Bar</div>'
                        ]
                    ],
                    ''
                ],
                '<div><div>Bar</div></div>'
            ],
            [
                [
                    '<div><!--###654b191b017dd268652de67882ae540e###--></div>',
                    [
                        'body.home~~~blog[1]' => [
                            'placeholder' => '<!--###654b191b017dd268652de67882ae540e###-->',
                            'modifier_lu' => '/INNER'
                        ]
                    ],
                    '1434ab9999d01bea613d8d01807adb01'
                ],
                '<div>1434ab9999d01bea613d8d01807adb01body.home~~~blog[1]/INNER1434ab9999d01bea613d8d01807adb01</div>'
            ],
            [
                [
                    '<div id="<!--###654b191b017dd268652de67882ae540e###-->" class="<!--###bf58b34b6f2b330c9f5463ac403d4409###-->">Foo</div>',
                    [
                        'body.home~~~blog[1]' => [
                            'attr' => [
                                'id' => [
                                    'placeholder' => '<!--###654b191b017dd268652de67882ae540e###-->',
                                    'content' => 'id'
                                ],
                                'class' => [
                                    'placeholder' => '<!--###bf58b34b6f2b330c9f5463ac403d4409###-->',
                                    'content' => 'class'
                                ]
                            ]
                        ]
                    ],
                    ''
                ],
                '<div id="id" class="class">Foo</div>'
            ],
            [
                [
                    '<div id="<!--###654b191b017dd268652de67882ae540e###-->">Foo</div>',
                    [
                        'body.home~~~blog[1]' => [
                            'attr' => [
                                'id' => [
                                    'placeholder' => '<!--###654b191b017dd268652de67882ae540e###-->',
                                    'content' => 'id'
                                ]
                            ]
                        ]
                    ],
                    '1434ab9999d01bea613d8d01807adb01'
                ],
                '<div id="1434ab9999d01bea613d8d01807adb01body.home~~~blog[1]/ATTR:id1434ab9999d01bea613d8d01807adb01">Foo</div>'
            ]
        ];
    }

    /**
     * @param array $params
     * @param string $expected
     *
     * @dataProvider testMergeSearchpartsIntoContentDataProvider
     */
    public function testMergeSearchpartsIntoContent($params, $expected)
    {
        /** @var AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject | HtmlMarkup $htmlMarkup */
        $htmlMarkup = $this->getAccessibleMock(HtmlMarkup::class, ['dummy']);

        list($content, $searchParts, $token) = array_values($params);

        static::assertSame(
            $expected,
            $htmlMarkup->_call('mergeSearchpartsIntoContent', $content, $searchParts, $token)
        );
    }

    /**
     * @return array
     */
    public function testMappingInfoToSearchPathDataProvider()
    {
        return [
            [
                [],
                []
            ],
            [
                [
                    'ROOT' => [
                        'MAP_EL' => 'body[1]'
                    ]
                ],
                [
                    'body[1]'
                ]
            ],
            [
                [
                    'ROOT' => [
                        'MAP_EL' => 'body[1]/INNER'
                    ]
                ],
                [
                    'body[1] / INNER'
                ]
            ],
            [
                [
                    'ROOT' => [
                        'MAP_EL' => 'body[1]/ATTR:class'
                    ]
                ],
                [
                    'body[1] / ATTR:class'
                ]
            ],
            [
                [
                    'ROOT' => [
                        'MAP_EL' => 'body[1]/RANGE:input.search-field[1]'
                    ]
                ],
                [
                    'body[1] / RANGE:input.search-field[1]'
                ]
            ],
            [
                [
                    'Foo' => [
                        'MAP_EL' => 'div#foo/INNER'
                    ],
                    'Bar' => [
                        'MAP_EL' => 'div#foo/ATTR:class'
                    ]
                ],
                [
                    'div#foo / INNER+ATTR:class'
                ]
            ]
        ];
    }

    /**
     * @param array $currentMappingInfo
     * @param string $expected
     *
     * @dataProvider testMappingInfoToSearchPathDataProvider
     */
    public function testMappingInfoToSearchPath($currentMappingInfo, $expected)
    {
        /** @var AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject | HtmlMarkup $htmlMarkup */
        $htmlMarkup = $this->getAccessibleMock(HtmlMarkup::class, ['dummy']);

        static::assertSame($expected, $htmlMarkup->_call('mappingInfoToSearchPath', $currentMappingInfo));
    }

    /**
     * @return array
     */
    public function testRecursiveBlockSplittingDataProvider()
    {
        return [
            [
                [
                    'content' => '',
                    'tagsBlock' => '',
                    'tagsSolo' => '',
                    'mode' => '',
                    'path' => ''
                ],
                ''
            ],
            [
                [
                    'content' => '<div>Foo</div>',
                    'tagsBlock' => 'div',
                    'tagsSolo' => '',
                    'mode' => '',
                    'path' => ''
                ],
                '<div>Foo</div>'
            ],
            [
                [
                    'content' => '<div>Foo</div>',
                    'tagsBlock' => 'div',
                    'tagsSolo' => '',
                    'mode' => 'markup',
                    'path' => ''
                ],
                '<div><span class="gnyfBox"><span  class="label label-primary gnyfElement gnyfGrouping" title="div[1]">div</span></span>Foo</div>'
            ],
            [
                [
                    'content' => '<div>Foo</div>',
                    'tagsBlock' => 'div',
                    'tagsSolo' => '',
                    'mode' => 'search',
                    'path' => ''
                ],
                '<div>Foo</div>'
            ],
            [
                [
                    'content' => '<head><!--[if lt IE 9]><script>script</script><!--[endif]--></head>',
                    'tagsBlock' => 'script,style',
                    'tagsSolo' => '',
                    'mode' => '',
                    'path' => ''
                ],
                "<head><!--[if lt IE 9]>\n<script></script><!--[endif]--></head>" // todo: this seems very wrong
            ],
            [
                [
                    'content' => '<div><img src="src" title="title" /></div>',
                    'tagsBlock' => 'div',
                    'tagsSolo' => 'img',
                    'mode' => '',
                    'path' => ''
                ],
                '<div><img src="src" title="title" /></div>'
            ],
            [
                [
                    'content' => '<div><img src="src" title="title" /><hr></div>',
                    'tagsBlock' => 'div',
                    'tagsSolo' => 'img,hr',
                    'mode' => '',
                    'path' => ''
                ],
                '<div><img src="src" title="title" /><hr></div>'
            ],
            [
                [
                    'content' => '<div><img src="src" title="title" /><hr></div>',
                    'tagsBlock' => 'div',
                    'tagsSolo' => 'img,hr',
                    'mode' => '',
                    'path' => ''
                ],
                '<div><img src="src" title="title" /><hr></div>'
            ],
            [
                [
                    'content' => '<script>script</script>',
                    'tagsBlock' => 'script',
                    'tagsSolo' => '',
                    'mode' => 'markup',
                    'path' => '',
                    'objectMode' => 'checkbox'
                ],
                '
                <tr class="bgColor4">
                    <td><input type="checkbox" name="checkboxElement[]" value="script[1]" /></td>
                    <td><span class="gnyfBox"><span  class="label label-primary gnyfElement gnyfDocument" title="script[1]">script</span></span></td>
                    <td><pre>&lt;script&gt;script&lt;/script&gt;</pre></td>
                </tr>'
            ]
        ];
    }

    /**
     * @param array $params
     * @param string $expected
     *
     * @dataProvider testRecursiveBlockSplittingDataProvider
     */
    public function testRecursiveBlockSplitting($params, $expected)
    {
        list($content, $tagsBlock, $tagsSolo, $mode, $path, $objectMode) = array_values($params);

        /** @var AccessibleObjectInterface | \PHPUnit_Framework_MockObject_MockObject | HtmlMarkup $htmlMarkup */
        $htmlMarkup = $this->getAccessibleMock(HtmlMarkup::class, ['dummy']);
        $htmlMarkup->_set('mode', $objectMode);

        static::assertSame($expected, $htmlMarkup->_call('recursiveBlockSplitting', $content, $tagsBlock, $tagsSolo, $mode, $path));
    }
}
