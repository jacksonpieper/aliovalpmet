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

namespace Schnitzler\TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Backend\Form\Container\AbstractContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormTabsContainer
 */
class FlexFormTabsContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $fieldName = $this->data['fieldName']; // field name of the flex form field in DB
        $parameterArray = $this->data['parameterArray'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormCurrentLanguage = $this->data['flexFormCurrentLanguage'];
        $flexFormRowData = $this->data['flexFormRowData'];

        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/Tabs';

        $domIdPrefix = 'DTM-' . GeneralUtility::shortMD5($this->data['parameterArray']['itemFormElName'] . $flexFormCurrentLanguage);
        $tabCounter = 0;
        $tabElements = array();
        foreach ($flexFormDataStructureArray['sheets'] as $sheetName => $sheetDataStructure) {
            $flexFormRowSheetDataSubPart = $flexFormRowData['data'][$sheetName][$flexFormCurrentLanguage];

            if (!is_array($sheetDataStructure['ROOT']['el'])) {
                $resultArray['html'] .= LF . 'No Data Structure ERROR: No [\'ROOT\'][\'el\'] found for sheet "' . $sheetName . '".';
                continue;
            }

            $tabCounter ++;

            // Assemble key for loading the correct CSH file
            // @todo: what is that good for? That is for the title of single elements ... see FlexFormElementContainer!
            $dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['ds_pointerField'], true);
            $parameterArray['_cshKey'] = $table . '.' . $fieldName;
            foreach ($dsPointerFields as $key) {
                if ((string)$row[$key] !== '') {
                    $parameterArray['_cshKey'] .= '.' . $row[$key];
                }
            }

            $options = $this->data;
            $options['flexFormDataStructureArray'] = $sheetDataStructure['ROOT']['el'];
            $options['flexFormRowData'] = $flexFormRowSheetDataSubPart;
            $options['flexFormFormPrefix'] = '[data][' . $sheetName . '][' . $flexFormCurrentLanguage . ']';
            $options['parameterArray'] = $parameterArray;
            // Merge elements of this tab into a single list again and hand over to
            // palette and single field container to render this group
            $options['tabAndInlineStack'][] = array(
                'tab',
                $domIdPrefix . '-' . $tabCounter,
            );
            $options['renderType'] = 'flexFormElementContainer';
            $childReturn = $this->nodeFactory->create($options)->render();

            $tabElements[] = array(
                'label' => !empty($sheetDataStructure['ROOT']['sheetTitle']) ? $languageService->sL(trim($sheetDataStructure['ROOT']['sheetTitle'])) : $sheetName,
                'content' => $childReturn['html'],
                'description' => $sheetDataStructure['ROOT']['sheetDescription'] ? $languageService->sL(trim($sheetDataStructure['ROOT']['sheetDescription'])) : '',
                'linkTitle' => $sheetDataStructure['ROOT']['sheetShortDescr'] ? $languageService->sL(trim($sheetDataStructure['ROOT']['sheetShortDescr'])) : '',
            );

            $childReturn['html'] = '';
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childReturn);
        }

        // Feed everything to document template for tab rendering
        $resultArray['html'] = $this->renderTabMenu($tabElements, $domIdPrefix);
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
