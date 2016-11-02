<?php
namespace Schnitzler\Templavoila\Form\FormDataProvider;

/*
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
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Class Schnitzler\Templavoila\Form\FormDataProvider\BeforeTcaFlexPrepare
 */
class BeforeTcaFlexPrepare implements FormDataProviderInterface
{

    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
                continue;
            }

            /*
             * Example:
             * <field_header>
             *    <tx_templavoila type="array">
             *        <type>array</type>
             *        <title>Header</title>
             *        <sample_data type="array">
             *             <numIndex index="0"></numIndex>
             *         </sample_data>
             *         <eType>input</eType>
             *         <proc type="array">
             *             <HSC type="integer">1</HSC>
             *             <stdWrap></stdWrap>
             *         </proc>
             *         <preview></preview>
             *     </tx_templavoila>
             *     <TCEforms type="array">
             *         <label>Header</label>
             *         <config type="array">
             *             <type>input</type>
             *             <size>48</size>
             *             <eval>trim</eval>
             *         </config>
             *     </TCEforms>
             * </field_header>
             *
             * TemplaVoilà usually holds a tx_templavoila node next to
             * the TCEforms node which is not a problem in general but
             * the form engine only respects fields that only have one
             * TCEforms child node.
             *
             * During the editing of records in the backend, that
             * additional information from that tx_templavoila node
             * is not needed, so it can be safely replaced here.
             */
            $result = $this->removeSuperfluousTemplaVoilaNodes($result, $fieldName);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param $fieldName
     * @return array
     */
    public function removeSuperfluousTemplaVoilaNodes(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $modifiedDataStructure = $this->removeSuperfluousTemplaVoilaNodesRecursive($modifiedDataStructure);
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }

    /**
     * @param array $structure
     * @return array
     */
    public function removeSuperfluousTemplaVoilaNodesRecursive(array $structure)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                /** @var array $value */
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue) && isset($subValue['tx_templavoila'])) {
                        unset($subValue['tx_templavoila']);
                    }
                    $newSubStructure[$subKey] = $subValue;
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->removeSuperfluousTemplaVoilaNodesRecursive($value);
            }
            $newStructure[$key] = $value;
        }

        return $newStructure;
    }
}
