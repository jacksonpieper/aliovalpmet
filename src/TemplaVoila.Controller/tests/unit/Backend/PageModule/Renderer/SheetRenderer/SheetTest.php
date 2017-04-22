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

namespace Schnitzler\TemplaVoila\Controller\Tests\Unit\Backend\PageModule\Renderer\SheetRenderer;

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\SheetRenderer\Column;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\SheetRenderer\Sheet;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\SheetRenderer\SheetTest
 */
class SheetTest extends UnitTestCase
{

    /**
     * @var Column
     */
    protected $column;

    public function setUp()
    {
        $this->column = new Column([], [], 'EN');
    }

    public function testWithMinimumConfiguration()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ]
            ],
            'IT'
        );

        $this->assertTrue($sheet->isLocalizable());
        $this->assertFalse($sheet->hasLocalizableChildren());
        $this->assertSame(12345, $sheet->getUid());
        $this->assertSame(23456, $sheet->getPid());
        $this->assertTrue($sheet->belongsToPage(23456));
        $this->assertFalse($sheet->belongsToPage(12345));
        $this->assertFalse($sheet->isContainerElement());
        $this->assertFalse($sheet->isFlexibleContentElement());
    }

    public function testHasLocalizableChildrenWithoutMeta()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ]
            ],
            'IT'
        );

        $this->assertFalse($sheet->hasLocalizableChildren());
    }

    public function testHasLocalizableChildrenWithLangChildrenSetTo0()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ],
                'ds_meta' => [
                    'langChildren' => 0
                ]
            ],
            'IT'
        );

        $this->assertFalse($sheet->hasLocalizableChildren());
    }

    public function testHasLocalizableChildrenWithLangChildrenSetTo1()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ],
                'ds_meta' => [
                    'langChildren' => 1
                ]
            ],
            'IT'
        );

        $this->assertTrue($sheet->hasLocalizableChildren());
    }

    public function testIsLocalizableWithoutMeta()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ]
            ],
            'IT'
        );

        $this->assertTrue($sheet->isLocalizable());
    }

    public function testIsLocalizableWithLangDisableSetTo0()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ],
                'ds_meta' => [
                    'langDisable' => 0
                ]
            ],
            'IT'
        );

        $this->assertTrue($sheet->isLocalizable());
    }

    public function testIsLocalizableWithLangDisableSetTo1()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'uid' => 12345,
                    'pid' => 23456,
                    'table' => 'pages'
                ],
                'ds_meta' => [
                    'langDisable' => 1
                ]
            ],
            'IT'
        );

        $this->assertFalse($sheet->isLocalizable());
    }

    public function testGetLanguageKeyWithoutMetaSetExcplicitly()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'table' => 'pages'
                ]
            ],
            'IT'
        );

        /*
         * langChildren defaults to 0
         * langDisable defaults to 0
         *
         * Thus, the sheet language is lEN, the one from the parent column
         */
        $this->assertSame('lEN', $sheet->getLanguageKey());
    }

    public function testGetLanguageKeyWithLangDisableSetTo1()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'ds_meta' => [
                    'langDisable' => 1,
                    'langChildren' => 0
                ],
                'el' => [
                    'table' => 'pages'
                ]
            ],
            'IT'
        );

        $this->assertFalse($sheet->isLocalizable());
        $this->assertFalse($sheet->hasLocalizableChildren());
        $this->assertSame('lDEF', $sheet->getLanguageKey());
    }

    public function testIsContainerElement()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'table' => 'pages'
                ],
                'sub' => [
                    'sDEF' => [
                        'foo'
                    ]
                ]
            ],
            'IT'
        );

        $this->assertTrue($sheet->isContainerElement());
    }

    public function testIsFlexibleContentElement()
    {
        $sheet = new Sheet(
            $this->column,
            [
                'el' => [
                    'table' => 'tt_content',
                    'CType' => 'templavoila_pi1'
                ]
            ],
            'IT'
        );

        $this->assertTrue($sheet->isFlexibleContentElement());
    }
}
