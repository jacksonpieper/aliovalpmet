<?php
declare(strict_types = 1);

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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\MainController;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\System\Traits\BackendUser;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Sidebar\RecordsTab
 */
class RecordsTab implements Renderable
{
    use LanguageService;
    use BackendUser;

    /**
     * @var MainController
     */
    private $controller;

    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $tables;

    /**
     * @var DatabaseRecordList
     */
    private $dblist;

    /**
     * @param MainController $controller
     * @param array $tables
     */
    public function __construct(MainController $controller, array $tables)
    {
        $this->controller = $controller;
        $this->tables = $tables;
        $this->table = $controller->getSetting('recordsView_table');
    }

    /**
     * @return string
     */
    public function render(): string
    {
        if (count($this->tables) === 0) {
            return '';
        }

        $content = '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">';
        $content .= '<tr class="bgColor4-20"><th colspan="3">&nbsp;</th></tr>';
        $content .= $this->renderTableSelector();
        $content .= $this->renderRecords();
        $content .= '</table>';

        return $content;
    }

    /**
     * Renders table selector.
     *
     * @return string Genrated content
     */
    private function renderTableSelector()
    {
        $content = '<tr class="bgColor4">';
        $content .= '<td width="20">&nbsp;</td>';
        $content .= '<td width="200">' . static::getLanguageService()->getLL('displayRecordsFrom') . '</td><td>';

        $link = '\'index.php?' . $this->controller->link_getParameters() . '&SET[recordsView_start]=0&SET[recordsView_table]=\'+this.options[this.selectedIndex].value'; // todo: adjust link
        $content .= '<select onchange="document.location.href=' . $link . '">';
        $content .= '<option value=""' . ($this->table === '' ? ' selected="selected"' : '') . '></options>';

        foreach ($this->tables as $table) {
            $t = htmlspecialchars($table);
            $title = static::getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
            $content .= '<option value="' . $t . '"' . ($this->table === $table ? ' selected="selected"' : '') . '>' . $title . ' (' . $t . ')' . '</option>';
        }

        $content .= '</select>';

        if ($this->table !== '') {
            $backpath = '../../../../typo3/';
            $params = '&edit[' . $this->table . '][' . $this->controller->getId() . ']=new';
            $content .= '&nbsp;&nbsp;';
            $content .= '<a title="' . static::getLanguageService()->getLL('createnewrecord') . '" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $backpath, '-1')) . '">';
            $content .= $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $content .= '</a>';
        }

        $content .= '</td></tr><tr class="bgColor4"><td colspan="2"></td></tr>';

        return $content;
    }

    /**
     * @return string
     */
    private function renderRecords()
    {
        $content = '';
        if ($this->table) {
            $this->dblist = GeneralUtility::makeInstance(DatabaseRecordList::class);
            $this->dblist->calcPerms = 0; // todo :change this
            $this->dblist->thumbs = static::getBackendUser()->uc['thumbnailsByDefault'];
            $this->dblist->returnUrl = 'index.php?' . $this->controller->link_getParameters();
            $this->dblist->allFields = 1;
            $this->dblist->localizationView = true;
            $this->dblist->showClipboard = false;
            $this->dblist->disableSingleTableView = true;
            $this->dblist->listOnlyInSingleTableMode = false;
//        $this->dblist->clickTitleMode = $this->modTSconfig['properties']['clickTitleMode'];
//        $this->dblist->alternateBgColors = (isset($this->controller->getSetting('recordsView_alternateBgColors')) ? (int)$this->controller->getSetting('recordsView_alternateBgColors') : false);
            $this->dblist->allowedNewTables = [$this->table];
            $this->dblist->newWizards = false;
            $this->dblist->tableList = $this->table;
            $this->dblist->itemsLimitPerTable = ($GLOBALS['TCA'][$this->table]['interface']['maxDBListItems'] ?
                $GLOBALS['TCA'][$this->table]['interface']['maxDBListItems'] :
                ((int)$this->controller->modTSconfig['properties']['recordDisplay_maxItems'] ?
                    (int)$this->controller->modTSconfig['properties']['recordDisplay_maxItems'] : 10));

//            parent::start($this->pObj->rootElementUid_pidForContent,
//                '', //$this->pObj->MOD_SETTINGS['recordsView_table'],
//                (int)$this->pObj->MOD_SETTINGS['recordsView_start']);

            $this->dblist->start(
                $this->controller->getPid(),
                $this->table,
                (int)$this->controller->getSetting('recordsView_start')
            );

            $this->dblist->generateList();
            $content = '<tr class="bgColor4"><td colspan="3" style="padding: 0 0 3px 3px">' . $this->dblist->HTMLcode . '</td></tr>';
        }

        return $content;
    }
}
