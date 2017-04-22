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

namespace Schnitzler\Templavoila\Controller\Backend\FunctionsModule;

use Schnitzler\TemplaVoila\Data\Domain\Repository\DataStructureRepository;
use Schnitzler\System\Traits\BackendUser;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This wizard renames a field in pages.tx_templavoila_flex, to avoid
 * a remapping
 *
 *
 */
class RenameFieldInPageFlexWizardController extends AbstractFunctionModule
{
    use BackendUser;

    /**
     * @return string
     */
    public function main()
    {
        if (static::getBackendUser()->isAdmin()) {
            if ((int)$this->pObj->id > 0) {
                return $this->showForm() . $this->executeCommand();
            } else {
                // should never happen, as function module catches this already,
                // but save is save ;)
                return 'Please select a page from the tree';
            }
        } else {
            $message = new FlashMessage(
                'Module only available for admins.',
                '',
                FlashMessage::ERROR
            );

            return $message->render();
        }
    }

    /**
     * @param int $uid
     *
     * @return array
     */
    protected function getAllSubPages($uid)
    {
        $completeRecords = BackendUtility::getRecordsByField('pages', 'pid', (string)$uid);
        $return = [$uid];
        if (count($completeRecords) > 0) {
            foreach ($completeRecords as $record) {
                $return = array_merge($return, $this->getAllSubPages($record['uid']));
            }
        }

        return $return;
    }

    /**
     * @return string
     */
    protected function executeCommand()
    {
        $buffer = '';

        if (GeneralUtility::_GP('executeRename') == 1) {
            if (GeneralUtility::_GP('sourceField') === GeneralUtility::_GP('destinationField')) {
                $message = new FlashMessage(
                    'Renaming a field to itself is senseless, execution aborted.',
                    '',
                    FlashMessage::ERROR
                );

                return $message->render();
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder
                ->getRestrictions()
                ->removeAll();

            $query = $queryBuilder
                ->count('uid, title')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->like(
                        'tx_templavoila_flex',
                        $queryBuilder->quote($queryBuilder->escapeLikeWildcards(GeneralUtility::_GP('sourceField')))
                    ),
                    $queryBuilder->expr()->notLike(
                        'tx_templavoila_flex',
                        $queryBuilder->quote($queryBuilder->escapeLikeWildcards(GeneralUtility::_GP('destinationField')))
                    ),
                    $queryBuilder->expr()->in('uid', implode(',', $this->getAllSubPages($this->pObj->id)))
                );

            $rows = $query->execute()->fetchAll();

            if (count($rows) > 0) {
                // build message for simulation
                $mbuffer = 'Affects ' . count($rows) . ': <ul>';
                foreach ($rows as $row) {
                    $mbuffer .= '<li>' . htmlspecialchars($row['title']) . ' (uid: ' . (int)$row['uid'] . ')</li>';
                }
                $mbuffer .= '</ul>';
                $message = new FlashMessage($mbuffer, '', FlashMessage::INFO);
                $buffer .= $message->render();
                unset($mbuffer);
                //really do it
                if (!GeneralUtility::_GP('simulateField')) {
                    $escapedSource = $queryBuilder->quote(GeneralUtility::_GP('sourceField'));
                    $escapedDest = $queryBuilder->quote(GeneralUtility::_GP('destinationField'));

                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                    $queryBuilder
                        ->getRestrictions()
                        ->removeAll();

                    $query = $queryBuilder
                        ->update('pages')
                        ->set('tx_templavoila_flex', 'REPLACE(tx_templavoila_flex, ' . $escapedSource . ', ' . $escapedDest . ')')
                        ->where(
                            $queryBuilder->expr()->like('tx_templavoila_flex', $escapedSource),
                            $queryBuilder->expr()->notLike('tx_templavoila_flex', $escapedDest),
                            $queryBuilder->expr()->in('uid', implode(',', $this->getAllSubPages($this->pObj->id)))
                        );

                    $query->execute();

                    $message = new FlashMessage('DONE', '', FlashMessage::OK);
                    $buffer .= $message->render();
                }
            } else {
                $message = new FlashMessage('Nothing to do, can´t find something to replace.', '', FlashMessage::ERROR);
                $buffer .= $message->render();
            }
        }

        return $buffer;
    }

    /**
     * @return string
     */
    protected function showForm()
    {
        $message = new FlashMessage(
            'This action can affect ' . count($this->getAllSubPages($this->pObj->id)) . ' pages, please ensure, you know what you do!, Please backup your TYPO3 Installation before running that wizard.',
            '',
            FlashMessage::WARNING
        );
        $buffer = $message->render();
        unset($message);
        $buffer .= '<form action="' . $this->getLinkModuleRoot() . '"><div id="formFieldContainer">';
        $options = $this->getDSFieldOptionCode();
        $buffer .= $this->addFormField('sourceField', '', 'select_optgroup', $options);
        $buffer .= $this->addFormField('destinationField', '', 'select_optgroup', $options);
        $buffer .= $this->addFormField('simulateField', '1', 'checkbox');
        $buffer .= $this->addFormField('executeRename', '1', 'hidden');
        $buffer .= $this->addFormField('submit', '', 'submit');
        $buffer .= '</div></form>';
        $this->getKnownPageDS();

        return $buffer;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $type
     * @param array $options
     *
     * @return string
     */
    protected function addFormField($name, $value = '', $type = 'text', $options = [])
    {
        if ($value === '') {
            $value = GeneralUtility::_GP($name);
        }
        switch ($type) {
            case 'checkbox':
                if (GeneralUtility::_GP($name) || $value) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }

                return '<div id="form-line-0">'
                . '<label for="' . $name . '" style="width:200px;display:block;float:left;">' . $this->getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:field_' . $name) . '</label>'
                . '<input type="checkbox" id="' . $name . '" name="' . $name . '" ' . $checked . ' value="1">'
                . '</div>';
                break;
            case 'submit':
                return '<div id="form-line-0">'
                . '<input type="submit" id="' . $name . '" name="' . $name . '" value="' . $this->getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:field_' . $name) . '">'
                . '</div>';
                break;
            case 'hidden':
                return '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
                break;
            case 'select_optgroup':
                $buffer = '';
                foreach ($options as $optgroup => $options) {
                    $buffer .= '<optgroup label="' . $optgroup . '">';
                    foreach ($options as $option) {
                        if ($value === $option) {
                            $buffer .= '<option selected>' . htmlspecialchars($option) . '</option>';
                        } else {
                            $buffer .= '<option>' . htmlspecialchars($option) . '</option>';
                        }
                    }
                    $buffer .= '</optgroup>';
                }

                return '<div id="form-line-0">'
                . '<label style="width:200px;display:block;float:left;" for="' . $name . '">' . $this->getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:field_' . $name) . '</label>'
                . '<select id="' . $name . '" name="' . $name . '">' . $buffer . '</select>'
                . '</div>';
                break;
            case 'text':
            default:
                return '<div id="form-line-0">'
                . '<label for="' . $name . '">' . $this->getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:field_' . $name) . '</label>'
                . '<input type="text" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '">'
                . '</div>';
        }
    }

    /**
     * @return string
     */
    protected function getLinkModuleRoot()
    {
        $urlParams = $this->pObj->MOD_SETTINGS;
        $urlParams['id'] = $this->pObj->id;

        return $this->pObj->doc->scriptID . '?' . GeneralUtility::implodeArrayForUrl(
            '',
            $urlParams
        );
    }

    /**
     * @return mixed
     */
    protected function getKnownPageDS()
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);

        return $dsRepo->getDatastructuresByScope(1);
    }

    /**
     * @return array
     */
    protected function getDSFieldOptionCode()
    {
        $dsList = $this->getKnownPageDS();
        $return = [];
        foreach ($dsList as $ds) {
            /* @var $ds \Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure */
            $return[$ds->getLabel()] = [];
            $t = $ds->getDataprotArray();
            foreach (array_keys($t['ROOT']['el']) as $field) {
                $return[$ds->getLabel()][] = $field;
            }
        }

        return $return;
    }
}
