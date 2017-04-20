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

namespace Schnitzler\Templavoila\Tests\Unit\Hook;

use PHPUnit_Framework_TestCase;
use Schnitzler\Templavoila\Hook\DataHandlerHook;
use TYPO3\CMS\Core\DataHandling\DataHandler as CoreDataHandler;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Tests\Unit\Hook\DataHandlerHookTest
 */
class DataHandlerHookTest extends UnitTestCase
{

    /**
     * @var CoreDataHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataHandler;

    /**
     * @var DataHandlerHook
     */
    protected $dataHandlerHook;

    public function setUp()
    {
        $this->dataHandler = PHPUnit_Framework_TestCase::getMockBuilder(CoreDataHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExcludeListArray', 'doesRecordExist'])
            ->getMock();

        $this->dataHandlerHook = GeneralUtility::makeInstance(DataHandlerHook::class);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturnsIncomingResIfTableIsNotPages()
    {
        $this->dataHandler->admin = true;

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'tt_content',
            1,
            [
                'title' => 'foo'
            ],
            2,
            $this->dataHandler
        );

        static::assertSame(2, $result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturnsIncomingResIfDataIsNotAnArray()
    {
        $this->dataHandler->admin = true;

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            null,
            2,
            $this->dataHandler
        );

        static::assertSame(2, $result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturnsIncomingResIfUserIsAdmin()
    {
        $this->dataHandler->admin = true;

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [
                'title' => 'foo'
            ],
            2,
            $this->dataHandler
        );

        static::assertSame(2, $result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturns1IfDataIsEmptyAndRecordExists()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([]);
        $this->dataHandler->expects($this->once())->method('doesRecordExist')->willReturn(true);

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [],
            2,
            $this->dataHandler
        );

        static::assertSame(1, $result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturns1IfDataIsEmptyAndRecordDoesNotExist()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([]);
        $this->dataHandler->expects($this->once())->method('doesRecordExist')->willReturn(false);

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [],
            2,
            $this->dataHandler
        );

        static::assertFalse($result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturns1IfFieldsAreDisabledForUpdate()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([]);
        $this->dataHandler->expects($this->once())->method('doesRecordExist')->willReturn(true);
        $this->dataHandler->data_disableFields = ['pages' => [1 => ['title' => true]]];
        $this->dataHandler->exclude_array = [];

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [
                'title' => 'foo'
            ],
            2,
            $this->dataHandler
        );

        static::assertSame(1, $result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturns1IfFieldsAreInExcludeList()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([ 'pages-title' ]);
        $this->dataHandler->expects($this->once())->method('doesRecordExist')->willReturn(true);
        $this->dataHandler->data_disableFields = [];
        $this->dataHandler->exclude_array = [ 'pages-title' ];

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [
                'title' => 'foo'
            ],
            2,
            $this->dataHandler
        );

        static::assertSame(1, $result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturnsFalseIfFielDataIsNotAnArray()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([]);
        $this->dataHandler->expects($this->never())->method('doesRecordExist');
        $this->dataHandler->data_disableFields = [];
        $this->dataHandler->exclude_array = [];

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [
                'title' => 'foo'
            ],
            2,
            $this->dataHandler
        );

        static::assertFalse($result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturnsFalseIfTcaIsNotSet()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([]);
        $this->dataHandler->expects($this->never())->method('doesRecordExist');
        $this->dataHandler->data_disableFields = [];
        $this->dataHandler->exclude_array = [];

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [
                'title' => [
                    'data' => []
                ]
            ],
            2,
            $this->dataHandler
        );

        static::assertFalse($result);
    }

    /**
     * @test
     */
    public function checkRecordUpdateAccessReturnsFalseIfTcaIsNotOfTypeFlex()
    {
        $this->dataHandler->admin = false;
        $this->dataHandler->expects($this->any())->method('getExcludeListArray')->willReturn([]);
        $this->dataHandler->expects($this->never())->method('doesRecordExist');
        $this->dataHandler->data_disableFields = [];
        $this->dataHandler->exclude_array = [];

        $GLOBALS['TCA']['pages']['columns']['title']['config']['type'] = 'no flex';

        $result = $this->dataHandlerHook->checkRecordUpdateAccess(
            'pages',
            1,
            [
                'title' => [
                    'data' => []
                ]
            ],
            2,
            $this->dataHandler
        );

        static::assertFalse($result);
    }
}
