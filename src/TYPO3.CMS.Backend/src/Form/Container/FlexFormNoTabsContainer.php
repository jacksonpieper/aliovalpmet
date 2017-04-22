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

/**
 * Class Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormNoTabsContainer
 */
class FlexFormNoTabsContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $fieldName = $this->data['fieldName']; // field name of the flex form field in DB
        $parameterArray = $this->data['parameterArray'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormCurrentLanguage = $this->data['flexFormCurrentLanguage'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $resultArray = $this->initializeResultArray();

        // Flex ds was normalized in flex provider to always have a sheet.
        // Determine this single sheet name, most often it ends up with sDEF, except if only one sheet was defined
        $sheetName = array_pop(array_keys($flexFormDataStructureArray['sheets']));
        $flexFormRowDataSubPart = $flexFormRowData['data'][$sheetName][$flexFormCurrentLanguage];

        // That was taken from GeneralUtility::resolveSheetDefInDS - no idea if it is important
        unset($flexFormDataStructureArray['meta']);

        if (!is_array($flexFormDataStructureArray['sheets'][$sheetName]['ROOT']['el'])) {
            $resultArray['html'] = 'Data Structure ERROR: No [\'ROOT\'][\'el\'] element found in flex form definition.';
            return $resultArray;
        }

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
        $options['flexFormDataStructureArray'] = $flexFormDataStructureArray['sheets'][$sheetName]['ROOT']['el'];
        $options['flexFormRowData'] = $flexFormRowDataSubPart;
        $options['flexFormFormPrefix'] = '[data][' . $sheetName . '][' . $flexFormCurrentLanguage . ']';
        $options['parameterArray'] = $parameterArray;

        $options['renderType'] = 'flexFormElementContainer';
        return $this->nodeFactory->create($options)->render();
    }
}
