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

use Schnitzler\Templavoila\Service\ApiService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 *
 */
class ApiServiceTest extends FunctionalTestCase
{

    /**
     * string
     */
    const CONTENT_ELEMENT_HEADER = '*** t3unit templavoila testcase content element ***';

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/version',
        'typo3/sysext/workspaces',
        'typo3conf/ext/templavoila'
    ];

    /**
     * @var ApiService
     */
    protected $api;

    /**
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected $dataHandler;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser;

    /**
     */
    public function setUp()
    {
        parent::setUp();
        $this->api = new ApiService();

        $this->dataHandler = $this->getMock('TYPO3\CMS\Core\DataHandling\DataHandler', ['dummy']);

        $fixtureTables = [
            'sys_language',
            'pages',
            'pages_language_overlay',
            'sys_template',
            'sys_workspace',
            'tx_templavoila_datastructure',
            'tx_templavoila_tmplobj',
        ];

        $fixtureRootPath = ORIGINAL_ROOT . 'typo3conf/ext/templavoila/Tests/Functional/Service/ApiServiceTestFixtures/';

        foreach ($fixtureTables as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $this->backendUser->setWorkspace(0);

        Bootstrap::getInstance()->initializeLanguageObject();
    }

    /**
     * @test
     */
    public function InsertElement()
    {
        $pageUid = 1;
        // Prepare the new content element:
        $row = $this->fixture_getContentElementRow_TEXT();

        $row['pid'] = $pageUid;

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '0'
        ];

        // run insertElement():
        $elementUid = $this->api->insertElement($destinationPointer, $row);

        self::assertTrue($elementUid !== false, 'Inserting a new element was not successful, insertElement() returned FALSE');

        // Check if the new record really exists:
        $fields = implode(',', array_keys($row)) . ',uid';
        $fetchedRow = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUid, $fields);

        $recordsAreTheSame = count(array_intersect_assoc($row, $fetchedRow)) == count($row);
        self::assertTrue($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');

        // Check if the new record has been inserted correctly into the references list in table "pages":
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], (string) $elementUid, 'The reference from the test page to the element created by insertElement() is not as expected!');

        // Prepare the A SECOND content element:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['pid'] = $pageUid;
        $row['bodytext'] = 'SECOND content element';

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '0' // Before first element
        ];

        // run insertElement():
        $secondElementUid = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($secondElementUid !== false, 'Inserting the second element was not successful, insertElement() returned FALSE');

        // Check if the new record really exists:
        $fields = implode(',', array_keys($row)) . ',uid';
        $fetchedRow = BackendUtility::getRecordRaw('tt_content', 'uid=' . $secondElementUid, $fields);

        $recordsAreTheSame = count(array_intersect_assoc($row, $fetchedRow)) == count($row);
        self::assertTrue($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');

        // Check if the new record has been inserted correctly before the first one:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');

        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $secondElementUid . ',' . $elementUid, 'The reference list the elements created by insertElement() is not as expected!');

        // Prepare the A THIRD content element:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['pid'] = 1;
        $row['bodytext'] = 'THIRD content element';

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '1', // After first element
            'targetCheckUid' => $secondElementUid
        ];

        // run insertElement():
        $thirdElementUid = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($thirdElementUid !== false, 'Inserting the third element was not successful, insertElement() returned FALSE');

        // Check if the new record really exists:
        $fields = implode(',', array_keys($row)) . ',uid';
        $fetchedRow = BackendUtility::getRecordRaw('tt_content', 'uid=' . $thirdElementUid, $fields);

        $recordsAreTheSame = count(array_intersect_assoc($row, $fetchedRow)) == count($row);
        self::assertTrue($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');

        // Check if the new record has been inserted correctly behind the second one:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $secondElementUid . ',' . $thirdElementUid . ',' . $elementUid, '(Third element) The reference list the elements created by insertElement() is not as expected!');
    }

    /**
     * @test
     */
    public function InsertElementWithWorkspaces()
    {
        $pageUid = 1;

        $this->backendUser->setWorkspace(1);

        // Prepare the new content element:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['pid'] = $pageUid;

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '0'
        ];

        // run insertElement():
        $elementUid = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($elementUid !== false, 'Inserting a new element was not successful, insertElement() returned FALSE');

        // Check if the new record really exists:
        $fields = implode(',', array_keys($row)) . ',uid';
        $fetchedRow = BackendUtility::getRecordWSOL('tt_content', $elementUid, $fields);

        $recordsAreTheSame =
            $row['CType'] == $fetchedRow['CType'] &&
            $row['header'] == $fetchedRow['header'] &&
            $row['bodytext'] == $fetchedRow['bodytext'] &&
            $elementUid == $fetchedRow['uid'] &&
            -1 == $fetchedRow['_ORIG_pid'];
        self::assertTrue($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');

        // Check if the new record has been inserted correctly into the references list in table "pages":
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'tx_templavoila_flex,uid,pid');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], (string) $elementUid, 'The reference from the test page to the element created by insertElement() is not as expected!');

        // Prepare the A SECOND content element:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['pid'] = $pageUid;
        $row['bodytext'] = 'SECOND content element';

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '0' // Before first element
        ];

        // run insertElement():
        $secondElementUid = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($secondElementUid !== false, 'Inserting the second element was not successful, insertElement() returned FALSE');

        // Check if the new record really exists:
        $fields = implode(',', array_keys($row)) . ',uid';
        $fetchedRow = BackendUtility::getRecordWSOL('tt_content', $secondElementUid, $fields);
        $recordsAreTheSame =
            $row['CType'] == $fetchedRow['CType'] &&
            $row['header'] == $fetchedRow['header'] &&
            $row['bodytext'] == $fetchedRow['bodytext'] &&
            $secondElementUid == $fetchedRow['uid'] &&
            -1 == $fetchedRow['_ORIG_pid'];
        self::assertTrue($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');

        // Check if the new record has been inserted correctly before the first one:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'tx_templavoila_flex,uid,pid');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $secondElementUid . ',' . $elementUid, 'The reference list the elements created by insertElement() is not as expected!');

        // Prepare the A THIRD content element:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['pid'] = $pageUid;
        $row['bodytext'] = 'THIRD content element';

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '1', // After first element
            'targetCheckUid' => $secondElementUid
        ];

        // run insertElement():
        $thirdElementUid = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($thirdElementUid !== false, 'Inserting the third element was not successful, insertElement() returned FALSE');

        // Check if the new record really exists:
        $fields = implode(',', array_keys($row)) . ',uid';
        $fetchedRow = BackendUtility::getRecordWSOL('tt_content', $thirdElementUid, $fields);

        $recordsAreTheSame =
            $row['CType'] == $fetchedRow['CType'] &&
            $row['header'] == $fetchedRow['header'] &&
            $row['bodytext'] == $fetchedRow['bodytext'] &&
            $thirdElementUid == $fetchedRow['uid'] &&
            -1 == $fetchedRow['_ORIG_pid'];
        self::assertTrue($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');

        // Check if the new record has been inserted correctly behind the second one:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'tx_templavoila_flex,uid,pid');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $secondElementUid . ',' . $thirdElementUid . ',' . $elementUid, '(Third element) The reference list the elements created by insertElement() is not as expected!');
    }

    /**
     * @test
     */
    public function insertElementReturnsFalseWithInvalidData()
    {
        $pageUid = 1;
        // Prepare the new content element:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['pid'] = $pageUid;

        $destinationPointer = [
            'table' => 'be_users',
            'uid' => 1,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => '1'
        ];

        // Try to insert the element with invalid parent table:
        $elementUid = $this->api->insertElement($destinationPointer, $row);
        self::assertFalse($elementUid, 'Trying to insert a content element into invalid table did not return FALSE!');
    }

    /**
     * @test
     */
    public function insertElementSetsSortingFieldCorrectly()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;

        // Create 3 new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'insert test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Check if the sorting field has been set correctly:
        $elementRecords = [];
        $elementRecords[1] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[1], 'uid,sorting');
        $elementRecords[2] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[2], 'uid,sorting');
        $elementRecords[3] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[3], 'uid,sorting');

        $orderIsCorrect = $elementRecords[1]['sorting'] < $elementRecords[2]['sorting'] && $elementRecords[2]['sorting'] < $elementRecords[3]['sorting'];
        self::assertTrue($orderIsCorrect, 'The sorting field has not been set correctly after inserting three CEs with insertElement()!');

        // Insert yet another element after the first:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['bodytext'] = 'insert test element #4';
        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        $elementUids[4] = $this->api->insertElement($destinationPointer, $row);

        // Check if the sorting field has been set correctly:
        $elementRecords = [];
        $elementRecords[1] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[1], 'uid,sorting');
        $elementRecords[2] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[2], 'uid,sorting');
        $elementRecords[3] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[3], 'uid,sorting');
        $elementRecords[4] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[4], 'uid,sorting');

        $orderIsCorrect =
            $elementRecords[1]['sorting'] < $elementRecords[4]['sorting'] &&
            $elementRecords[4]['sorting'] < $elementRecords[2]['sorting'] &&
            $elementRecords[2]['sorting'] < $elementRecords[3]['sorting'];
        self::assertTrue($orderIsCorrect, 'The sorting field has not been set correctly after inserting a forth CE after the first with insertElement()!');
    }

    /**
     * @test
     */
    public function InsertElementWithOldStyleColumnNumber()
    {
        $pageUid = 2;
        $this->dataHandler->stripslashes_values = 0;

        // Create 2 new content elements, one in the main area and one in the right bar:
        $elementUids = [];

        $row = $this->fixture_getContentElementRow_TEXT();
        $row['bodytext'] = 'oldStyleColumnNumber test #1';
        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 0
        ];
        $elementUids[1] = $this->api->insertElement($destinationPointer, $row);

        $row = $this->fixture_getContentElementRow_TEXT();
        $row['bodytext'] = 'oldStyleColumnNumber test #2';
        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_rightbar',
            'vLang' => 'vDEF',
            'position' => 0
        ];
        $elementUids[2] = $this->api->insertElement($destinationPointer, $row);

        $elementRecords = [];
        $elementRecords[1] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[1], 'uid,sorting,colpos');
        $elementRecords[2] = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[2], 'uid,sorting,colpos');

        self::assertTrue($elementRecords[1]['colpos'] == 0 && $elementRecords[2]['colpos'] == 1, 'The column position stored in the "colpos" field is not correct after inserting two content elements!');
    }

    /**
     * @test
     *
     * Checks a special situation while inserting CEs if elements have been deleted
     * before. See bug #3042
     */
    public function InsertElementResolvesBug3042Part1()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;
        $this->backendUser->setWorkspace(-1);

        // Create 3 new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'insert test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];

            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Delete the second content element by calling TCEmain instead of using the TemplaVoila API.
        // We pass the UID of the CE with the content (overlayed UID), not the UID of the placeholder
        // record because that exposes the bug.

        $this->dataHandler->stripslashes_values = 0;

        $cmdMap = [
            'tt_content' => [
                BackendUtility::wsMapId('tt_content', $elementUids[2]) => [
                    'delete' => 1
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdMap);
        $this->dataHandler->process_cmdmap();

        // Now insert an element after the second:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['bodytext'] = 'insert test element #4';
        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $elementUids[4] = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($elementUids[4] !== false, 'Bug 3042 part one - Inserting a new element was not successful, insertElement() returned FALSE');

        // Check if the new record has been inserted correctly behind the second one:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'tx_templavoila_flex,uid,pid');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[3] . ',' . $elementUids[4], 'insertElement_bug3042 - The pages reference list of the elements I created and deleted is not as expected!');
    }

    /**
     * @test
     *
     * Checks a special situation while inserting CEs if elements have been deleted
     * before. See bug #3042
     */
    public function InsertElementResolvesBug3042Part2()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;
        $this->backendUser->setWorkspace(-1);

        // Create 3 new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'insert test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];

            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        //Mark the second content element as deleted directly in the database so TemplaVoila has no
        // chance to clean up the flexform XML and therefore must handle the inconsistency:

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->update('tt_content')
            ->where($queryBuilder->expr()->eq('uid', (int)$elementUids[2]))
            ->set('deleted', 1)
            ->execute();

        // Now insert an element after the second:
        $row = $this->fixture_getContentElementRow_TEXT();
        $row['bodytext'] = 'insert test element #4';
        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];
        $elementUids[4] = $this->api->insertElement($destinationPointer, $row);
        self::assertTrue($elementUids[4] !== false, 'Bug 3042 Part two - Inserting a new element was not successful, insertElement() returned FALSE');

        // Check if the new record has been inserted correctly behind the second one:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'tx_templavoila_flex,uid,pid');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[3] . ',' . $elementUids[4], 'insertElement_bug3042 - The pages reference list of the elements I created and deleted is not as expected!');
    }

    /**
     * @test
     */
    public function MoveElementOnSamePage()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;
        $this->backendUser->setWorkspace(-1);

        // Create 3 new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Cut first element and paste it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Move the element within the same page with valid source and destination pointer:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the first element has been moved correctly behind the third one:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[2] . ',' . $elementUids[3] . ',' . $elementUids[1], 'The reference list is not as expected after moving the first element after the third with moveElement()!');

        // Cut third element and paste it after the first:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        // Move the element within the same page with valid source and destination pointer:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the first element has been moved correctly behind the third one:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[2] . ',' . $elementUids[1] . ',' . $elementUids[3], 'The reference list is not as expected after moving the third element after the first with moveElement()!');

        // Try to move the element with invalid source pointer:
        $sourcePointer['position'] = 9999;
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertFalse($result, 'moveElement() did not return FALSE although we tried to move an element specified by an invalid source pointer!');
    }

    /**
     * @test
     */
    public function MoveElementOnSamePageWithinFCE()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;

        // Create a 2-column FCE:
        $row = $this->fixture_getContentElementRow_FCE(3, 3);
        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 0
        ];
        $FCEUid = $this->api->insertElement($destinationPointer, $row);

        // Create 3+3 new content elements within the two columns of the FCE:
        $elementUidsLeft = [];
        $elementUidsRight = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element left #' . $i;
            $destinationPointer = [
                'table' => 'tt_content',
                'uid' => $FCEUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_leftcolumn',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUidsLeft[($i + 1)] = $this->api->insertElement($destinationPointer, $row);

            $row['bodytext'] = 'move test element right #' . $i;
            $destinationPointer = [
                'table' => 'tt_content',
                'uid' => $FCEUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_rightcolumn',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUidsRight[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Right column: cut first element and paste it after the third:
        $sourcePointer = [
            'table' => 'tt_content',
            'uid' => $FCEUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_rightcolumn',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        $destinationPointer = [
            'table' => 'tt_content',
            'uid' => $FCEUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_rightcolumn',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Move the element within the same FCE with valid source and destination pointer:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the first element has been moved correctly behind the third one:
        $testFCERecord = BackendUtility::getRecordRaw('tt_content', 'uid=' . $FCEUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testFCERecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightcolumn']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUidsRight[2] . ',' . $elementUidsRight[3] . ',' . $elementUidsRight[1], 'The reference list is not as expected after moving the first element after the third with moveElement()!');

        // Cut third element of the right column and paste it after the first in the left column:
        $sourcePointer = [
            'table' => 'tt_content',
            'uid' => $FCEUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_rightcolumn',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        $destinationPointer = [
            'table' => 'tt_content',
            'uid' => $FCEUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_leftcolumn',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        // Move the element within the same FCE with valid source and destination pointer from one column to another:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the first element has been moved correctly behind the first one in the other column:
        $testFCERecord = BackendUtility::getRecordRaw('tt_content', 'uid=' . $FCEUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testFCERecord['tx_templavoila_flex']);

        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightcolumn']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUidsRight[2] . ',' . $elementUidsRight[3], 'The reference list in the right column is not as expected after moving the third element of the second column to after the first in the first column with moveElement()!');
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_leftcolumn']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUidsLeft[1] . ',' . $elementUidsRight[1] . ',' . $elementUidsLeft[2] . ',' . $elementUidsLeft[3], 'The reference list in the left column is not as expected after moving the third element of the second column to after the first in the first column with moveElement()!');
    }

    /**
     * @test
     */
    public function MoveElementOnSamePageWithWorkspaces()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;
        $this->backendUser->setWorkspace(-1);

        // Create 3 new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Cut first element and paste it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Move the element within the same page with valid source and destination pointer:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the first element has been moved correctly behind the third one:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'uid,pid,tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[2] . ',' . $elementUids[3] . ',' . $elementUids[1], 'The reference list is not as expected after moving the first element after the third with moveElement()!');

        // Cut third element and paste it after the first:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        // Move the element within the same page with valid source and destination pointer:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the first element has been moved correctly behind the third one:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'uid,pid,tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[2] . ',' . $elementUids[1] . ',' . $elementUids[3], 'The reference list is not as expected after moving the third element after the first with moveElement()!');

        // Try to move the element with invalid source pointer:
        $sourcePointer['position'] = 9999;
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertFalse($result, 'moveElement() did not return FALSE although we tried to move an element specified by an invalid source pointer!');
    }

    /**
     * @test
     */
    public function MoveElementToAnotherPage()
    {
        $pageUid = 1;
        $targetPageUid = 2;

        // Create 3 new content elements on test page and on target page:
        $sourcePageElementUids = [];
        $targetPageElementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $sourcePageElementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);

            $row['bodytext'] = 'move test element (destination page) #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $targetPageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $targetPageElementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Cut second element from source test page and paste it after the first of the target page:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $targetPageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1,
            'targetCheckUid' => $targetPageElementUids[1]
        ];

        // Move the element:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the element has been referenced correctly on the destination page:
        $targetTestPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $targetPageUid, 'tx_templavoila_flex,pid');
        $flexform = simplexml_load_string($targetTestPageRecord['tx_templavoila_flex']);
        $expectedReferences = $targetPageElementUids[1] . ',' . $sourcePageElementUids[2] . ',' . $targetPageElementUids[2] . ',' . $targetPageElementUids[3];
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $expectedReferences, 'The reference list is not as expected after moving the element from one page to another with moveElement()!');

        // Check if the element has the correct PID:
        $elementRecord = BackendUtility::getRecordRaw('tt_content', 'uid=' . $sourcePageElementUids[2], 'pid');
        self::assertEquals($targetPageUid, (integer) $elementRecord['pid'], 'The PID of the moved element has not been set to the new page uid!');
    }

    /**
     * @test
     */
    public function MoveElementToAnotherPageWithWorkspaces()
    {
        $pageUid = 1;
        $targetPageUid = 2;
        $this->backendUser->setWorkspace(-1);

        // Create 3 new content elements on test page and on target page:
        $sourcePageElementUids = [];
        $targetPageElementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $sourcePageElementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);

            $row['bodytext'] = 'move test element (destination page) #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $targetPageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $targetPageElementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Cut second element from source test page and paste it after the first of the target page:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $targetPageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1,
            'targetCheckUid' => $targetPageElementUids[1]
        ];

        // Move the element:
        $result = $this->api->moveElement($sourcePointer, $destinationPointer);
        self::assertTrue($result, 'moveElement() did not return TRUE!');

        // Check if the element has been referenced correctly on the destination page:
        $targetTestPageRecord = BackendUtility::getRecordWSOL('pages', $targetPageUid, 'uid,pid,tx_templavoila_flex');
        $flexform = simplexml_load_string($targetTestPageRecord['tx_templavoila_flex']);
        $expectedReferences = $targetPageElementUids[1] . ',' . $sourcePageElementUids[2] . ',' . $targetPageElementUids[2] . ',' . $targetPageElementUids[3];
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $expectedReferences, 'The reference list is not as expected after moving the element from one page to another with moveElement()!');

        // Check if the element has the correct PID:
        $elementRecord = BackendUtility::getRecordWSOL('tt_content', $sourcePageElementUids[2], 'uid,pid');
        self::assertEquals($targetPageUid, (integer) $elementRecord['pid'], 'The PID of the moved element has not been set to the new page uid!');
    }

    /**
     * @test
     */
    public function CopyElementOnSamePage()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Copy second element and paste it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Copy the element within the same page with valid source and destination pointer:
        $result = $this->api->copyElement($sourcePointer, $destinationPointer);
        self::assertTrue($result !== false, 'copyElement()returned FALSE!');

        // Check if the element has been copied correctly:
        $elementUids[4] = $result;
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[2] . ',' . $elementUids[3] . ',' . $elementUids[4], 'The reference list is not as expected after copying the second element after the third with copyElement()!');
    }

    /**
     * @test
     */
    public function CopyElementToAnotherPage()
    {
        $pageUid = 1;
        $targetPageUid = 2;

        // Create 3 new content elements on test page and on target page:
        $sourcePageElementUids = [];
        $targetPageElementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copy test element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $sourcePageElementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);

            $row['bodytext'] = 'copy test element (destination page) #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $targetPageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $targetPageElementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Copy first element and from one page and paste it after the second of the other page:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 1
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $targetPageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        // Copy the element:
        $result = $this->api->copyElement($sourcePointer, $destinationPointer);
        self::assertTrue($result !== false, 'copyElement() to different page returned FALSE!');

        // Check if the element has been copied correctly:
        $newElementUid = $result;
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $targetPageUid, 'tx_templavoila_flex');
        $expectedReferences = $targetPageElementUids[1] . ',' . $targetPageElementUids[2] . ',' . $newElementUid . ',' . $targetPageElementUids[3];
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $expectedReferences, 'The reference list is not as expected after copying the from one page to another with copyElement()!');
    }

    /**
     * @test
     */
    public function ReferenceElement()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Take second element and reference it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Reference the element within the same page with valid source and destination pointer:
        $result = $this->api->referenceElement($sourcePointer, $destinationPointer);
        self::assertTrue($result !== false, 'referenceElement() did FALSE!');

        // Check if the element has been referenced correctly:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[2] . ',' . $elementUids[3] . ',' . $elementUids[2], 'The reference list is not as expected after inserting a reference of the second element after the third with referenceElement()!');
    }

    /**
     * @test
     */
    public function ReferenceElementWithWorkspaces()
    {
        $pageUid = 1;
        $this->backendUser->setWorkspace(-1);

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Take second element and reference it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Reference the element within the same page with valid source and destination pointer:
        $result = $this->api->referenceElement($sourcePointer, $destinationPointer);
        self::assertTrue($result !== false, 'referenceElement() did FALSE!');

        // Check if the element has been referenced correctly:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'uid,pid,tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[2] . ',' . $elementUids[3] . ',' . $elementUids[2], 'The reference list is not as expected after inserting a reference of the second element after the third with referenceElement()!');
    }

    /**
     * @test
     */
    public function ReferenceElementByUid()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        $destinationPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 3
        ];

        // Reference the element within the same page with valid source and destination pointer:
        $result = $this->api->referenceElementByUid($elementUids[2], $destinationPointer);
        self::assertTrue($result !== false, 'referenceElement() returned FALSE!');

        // Check if the element has been referenced correctly:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[2] . ',' . $elementUids[3] . ',' . $elementUids[2], 'The reference list is not as expected after inserting a reference of the second element after the third with referenceElementByUid()!');
    }

    /**
     * @test
     */
    public function UnlinkElement()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Unlink the second element:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $result = $this->api->unlinkElement($sourcePointer);
        self::assertTrue($result !== false, 'unlinkElement() returned FALSE!');

        // Check if the element has been un-referenced correctly:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[3], 'The reference list is not as expected after unlinking an elemen with unlinkElement()!');
    }

    /**
     * @test
     */
    public function DeleteElement()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Unlink the second element:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $result = $this->api->deleteElement($sourcePointer);
        self::assertTrue($result !== false, 'deleteElement() returned FALSE!');

        // Check if the element has been un-referenced correctly:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[3], 'The reference list is not as expected after deleting an element with deleteElement()!');

        // Check if the record really has been deleted:
        $elementRecord = BackendUtility::getRecordRaw('tt_content', 'uid=' . $elementUids[2], '*');
        self::assertEquals((integer) $elementRecord['deleted'], 1, 'The element record has not been deleted correctly after calling deleteElement()!');
    }

    /**
     * @test
     */
    public function DeleteElementWithWorkspaces()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'copytest element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Unlink the second element:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $this->backendUser->setWorkspace(1);

        $result = $this->api->deleteElement($sourcePointer);
        self::assertTrue($result !== false, 'deleteElement() returned FALSE!');

        // Check if the element has been un-referenced correctly:
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'uid,pid,tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[3], 'The reference list is not as expected after deleting an element with deleteElement()!');

        // Check if the record really has been deleted:
        $elementRecord = BackendUtility::getRecordWSOL('tt_content', $elementUids[2], '*');
        self::assertEquals((integer) $elementRecord['t3ver_state'], 2, 'The element record has not been deleted correctly after calling deleteElement()!');
    }

    /**
     * @test
     */
    public function GetRecordByPointer()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'getRecordByPointer test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        $row['bodytext'] = 'getRecordByPointer test element #2';
        $flexformPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        // Fetch the record:
        $fetchedRow = $this->api->flexform_getRecordByPointer($flexformPointer);
        self::assertTrue($fetchedRow !== false, 'flexform_getRecordByPointer() returned FALSE!');

        $recordsAreTheSame = count(array_intersect_assoc($row, $fetchedRow)) == count($row);
        self::assertTrue($recordsAreTheSame, 'The record returned by flexform_getRecordByPointer() was not the one we expected!');
    }

    /**
     * @test
     */
    public function GetRecordByPointerWithWorkspaces()
    {
        $pageUid = 1;
        $this->backendUser->setWorkspace(1);

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'getRecordByPointer test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        $row['bodytext'] = 'getRecordByPointer test element #2';
        $flexformPointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        // Fetch the record:
        $fetchedRow = $this->api->flexform_getRecordByPointer($flexformPointer);
        self::assertTrue($fetchedRow !== false, 'flexform_getRecordByPointer() returned FALSE!');

        $recordsAreTheSame = count(array_intersect_assoc($row, $fetchedRow)) == count($row);
        self::assertTrue($recordsAreTheSame, 'The record returned by flexform_getRecordByPointer() was not the one we expected!');
    }

    /**
     * @test
     */
    public function DataStructureGetsFieldNameByColumnPosition()
    {
        $pageUid = 1;

        $result = $this->api->ds_getFieldNameByColumnPosition($pageUid, 0);
        self::assertEquals($result, 'field_content', 'ds_getFieldNameByColumnPosition did not return the expected result!');
    }

    /**
     * @test
     */
    public function LocalizeElement()
    {
        $pageUid = 1;

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'localize test element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Copy second element and paste it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $result = $this->api->localizeElement($sourcePointer, 'DE');
        self::assertTrue($result !== false, 'localizeElement()returned FALSE!');

        // Check if the localized element has been referenced correctly:
        $localizedUid = (int)$result;
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDE']");
        self::assertEquals((string) $xpathResArr[0], (string) $localizedUid, 'The reference list is not as expected after localizing the second element to German!');

        // Check if the record has been modified correctly:
        $localizedRecord = BackendUtility::getRecordRaw('tt_content', 'uid=' . $localizedUid, '*');

        $isOkay = (
            $localizedRecord['l18n_parent'] == $elementUids[2] &&
            $localizedRecord['sys_language_uid'] == 1
        );

        self::assertTrue($isOkay, 'The localized record has not the expected content!');
    }

    /**
     * @test
     */
    public function LocalizeElementWithWorkspaces()
    {
        $pageUid = 1;
        $this->backendUser->setWorkspace(1);

        // Create new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'localize test element #' . $i;
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Copy second element and paste it after the third:
        $sourcePointer = [
            'table' => 'pages',
            'uid' => $pageUid,
            'sheet' => 'sDEF',
            'sLang' => 'lDEF',
            'field' => 'field_content',
            'vLang' => 'vDEF',
            'position' => 2
        ];

        $result = $this->api->localizeElement($sourcePointer, 'DE');
        self::assertTrue($result !== false, 'localizeElement()returned FALSE!');

        // Check if the localized element has been referenced correctly:
        $localizedUid = (int)$result;
        $testPageRecord = BackendUtility::getRecordWSOL('pages', $pageUid, 'uid,pid,tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDE']");
        self::assertEquals((string) $xpathResArr[0], (string) $localizedUid, 'The reference list is not as expected after localizing the second element to German!');

        // Check if the record has been modified correctly:
        $localizedRecord = BackendUtility::getRecordWSOL('tt_content', $localizedUid, '*');

        $isOkay = (
            $localizedRecord['l18n_parent'] == $elementUids[2] &&
            $localizedRecord['sys_language_uid'] == 1
        );

        self::assertTrue($isOkay, 'The localized record has not the expected content!');
    }

    /**
     * @test
     */
    public function moveElementUpUsingDataHandler()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;

        // Create 3 new content elements:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Move the third element to after the first element via TCEmain:
        $cmdMap = [
            'tt_content' => [
                $elementUids[3] => [
                    'move' => '-' . $elementUids[1]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdMap);
        $this->dataHandler->process_cmdmap();

        // Check if the third element has been moved correctly behind the first:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);
        $xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        self::assertEquals((string) $xpathResArr[0], $elementUids[1] . ',' . $elementUids[3] . ',' . $elementUids[2], 'The reference list is not as expected after moving the third element after the first with TCEmain()!');
    }

    /**
     * @test
     */
    public function moveElementUpUsingDataHandlerResolvesBug2154()
    {
        $pageUid = 1;
        $this->dataHandler->stripslashes_values = 0;

        // Create 3 new content elements in the main area and 3 in the right bar:
        $elementUids = [];
        for ($i = 0; $i < 3; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_content',
                'vLang' => 'vDEF',
                'position' => $i
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        for ($i = 3; $i < 6; $i++) {
            $row = $this->fixture_getContentElementRow_TEXT();
            $row['bodytext'] = 'move test element (right bar) #' . ($i + 1);
            $destinationPointer = [
                'table' => 'pages',
                'uid' => $pageUid,
                'sheet' => 'sDEF',
                'sLang' => 'lDEF',
                'field' => 'field_rightbar',
                'vLang' => 'vDEF',
                'position' => $i - 3
            ];
            $elementUids[($i + 1)] = $this->api->insertElement($destinationPointer, $row);
        }

        // Main area: move the third element to after the first element via TCEmain:
        $cmdMap = [
            'tt_content' => [
                $elementUids[3] => [
                    'move' => '-' . $elementUids[1]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdMap);
        $this->dataHandler->process_cmdmap();

        // ... and then move it one more up (exposes the bug 2154):
        $cmdMap = [
            'tt_content' => [
                $elementUids[3] => [
                    'move' => '-' . $elementUids[1]
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdMap);
        $this->dataHandler->process_cmdmap();

        // Check if the elements are in the right columns in the right order:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);

        $fieldContent_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        $fieldRightBar_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightbar']/value[@index='vDEF']");

        $everythingIsFine =
            (string) $fieldContent_xpathResArr[0] === $elementUids[3] . ',' . $elementUids[1] . ',' . $elementUids[2] &&
            (string) $fieldRightBar_xpathResArr[0] === $elementUids[4] . ',' . $elementUids[5] . ',' . $elementUids[6];

        self::assertTrue($everythingIsFine, 'The reference list is not as expected after moving the third element up two times in the left column!');

        // ... and then move the now second element one up again, measured by the sorting field! (also exposes the bug 2154):
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $query = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('pid', (int)$pageUid))
            ->orderBy('sorting', 'ASC');

        $elementsBySortingFieldArr = $query->execute()->fetchAll();

        $positionOfElement1 = null;
        foreach ($elementsBySortingFieldArr as $index => $row) {
            if ($elementUids[1] == $row['uid']) {
                $positionOfElement1 = $index;
            }
        }

        $cmdMap = [
            'tt_content' => [
                $elementUids[1] => [
                    'move' => '-' . $elementsBySortingFieldArr[$positionOfElement1 - 1]['uid']
                ]
            ]
        ];
        $this->dataHandler->start([], $cmdMap);
        $this->dataHandler->process_cmdmap();

        // Check again if the elements are in the right columns in the right order:
        $testPageRecord = BackendUtility::getRecordRaw('pages', 'uid=' . $pageUid, 'tx_templavoila_flex');
        $flexform = simplexml_load_string($testPageRecord['tx_templavoila_flex']);

        $fieldContent_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
        $fieldRightBar_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightbar']/value[@index='vDEF']");

        $everythingIsFine =
            (string) $fieldContent_xpathResArr[0] === $elementUids[1] . ',' . $elementUids[3] . ',' . $elementUids[2] &&
            (string) $fieldRightBar_xpathResArr[0] === $elementUids[4] . ',' . $elementUids[5] . ',' . $elementUids[6];

        self::assertTrue($everythingIsFine, 'The reference list is not as expected after moving the second element up and choosing the destination by the sorting field!');
    }

    /**
     * @return array
     */
    protected function fixture_getContentElementRow_TEXT()
    {
        return [
            'CType' => 'text',
            'header' => static::CONTENT_ELEMENT_HEADER,
            'bodytext' => 'T3Unit - If you see this message it appears that T3Unit succeeded in creating a content element at the test page. But usually you will never see this message. If everything runs fine.',
        ];
    }

    /**
     * @param int $dataStructureUid
     * @param int $templateObjectUid
     *
     * @return array
     */
    protected function fixture_getContentElementRow_FCE($dataStructureUid, $templateObjectUid)
    {
        return [
            'CType' => 'templavoila_pi1',
            'header' => static::CONTENT_ELEMENT_HEADER,
            'tx_templavoila_ds' => $dataStructureUid,
            'tx_templavoila_to' => $templateObjectUid,
        ];
    }
}
