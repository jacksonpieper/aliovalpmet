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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormEntryContainer
 */
class FlexFormEntryContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $flexFormDataStructureArray = $this->data['parameterArray']['fieldConf']['config']['ds'];
        $flexFormRowData = $this->data['parameterArray']['itemFormElValue'];

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // Tabs or no tabs - that's the question
        $hasTabs = false;
        if (count($flexFormDataStructureArray['sheets']) > 1) {
            $hasTabs = true;
        }

        $resultArray = $this->initializeResultArray();

        foreach ($flexFormDataStructureArray['meta']['languagesOnSheetLevel'] as $lKey) {
            // Add language as header
            if (!$flexFormDataStructureArray['meta']['langChildren'] && !$flexFormDataStructureArray['meta']['langDisable']) {
                // Find language uid of this iso code
                $languageUid = 0;
                if ($lKey !== 'DEF') {
                    foreach ($this->data['systemLanguageRows'] as $systemLanguageRow) {
                        if (strtoupper($systemLanguageRow['iso']) === $lKey) {
                            $languageUid = $systemLanguageRow['uid'];
                            break;
                        }
                    }
                }
                $resultArray['html'] .= LF
                    . '<strong>'
                    . $iconFactory->getIcon($this->data['systemLanguageRows'][$languageUid]['flagIconIdentifier'], Icon::SIZE_SMALL)->render()
                    . htmlspecialchars($this->data['systemLanguageRows'][$languageUid]['title'])
                    . '</strong>';
            }

            // Default language "lDEF", other options are "lUK" or whatever country code
            $flexFormCurrentLanguage = 'l' . $lKey;

            $options = $this->data;
            $options['flexFormCurrentLanguage'] = $flexFormCurrentLanguage;
            $options['flexFormDataStructureArray'] = $flexFormDataStructureArray;
            $options['flexFormRowData'] = $flexFormRowData;
            if (!$hasTabs) {
                $options['renderType'] = 'flexFormNoTabsContainer';
                $flexFormNoTabsResult = $this->nodeFactory->create($options)->render();
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormNoTabsResult);
            } else {
                $options['renderType'] = 'flexFormTabsContainer';
                $flexFormTabsContainerResult = $this->nodeFactory->create($options)->render();
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormTabsContainerResult);
            }
        }
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/FormEngineFlexForm';

        return $resultArray;
    }
}
