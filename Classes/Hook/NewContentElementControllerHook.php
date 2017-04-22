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

namespace Schnitzler\Templavoila\Hook;

use Schnitzler\Templavoila\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Domain\Model\Template;
use Schnitzler\Templavoila\Domain\Repository\TemplateRepository;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Hook\NewContentElementControllerHook
 */
class NewContentElementControllerHook
{
    use LanguageService;

    /**
     * @param array $wizardItems
     * @return array
     */
    public function proc($wizardItems)
    {
        /** @var TemplateRepository $templateRepository */
        $templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
        $templates = $templateRepository->findByScope(AbstractDataStructure::SCOPE_FCE);

        $pageTsConfig = BackendUtility::getPagesTSconfig((int)GeneralUtility::_GP('id'));

        // todo: this needs to be outsourced to a configuration manager
        $storagePid = (int)$pageTsConfig['mod.']['tx_templavoila.']['storagePid'];

        foreach ($templates as $template) {
            /** @var Template $template  */
            if ($template->isPermittedForUser() && ($storagePid === 0 || $template->isOnPage($storagePid))) {
                $identifier = 'extensions-templavoila-type-fce';

                $icon = $template->getIcon();
                if (file_exists($icon)) {
                    $identifier .= md5($icon);

                    /** @var IconRegistry $iconRegistry */
                    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
                    $iconRegistry->registerIcon(
                        $identifier,
                        BitmapIconProvider::class, [
                            'source' => $icon
                        ]
                    );
                }

                $wizardItems['fce_fce_' . $template->getKey()] = [
                    'iconIdentifier' => $identifier,
                    'description' => $template->getDescription() ? htmlspecialchars($template->getDescription()) : static::getLanguageService()->getLL('template_nodescriptionavailable'),
                    'title' => $template->getLabel(),
                    'tt_content_defValues.' => $this->getDsDefaultValues($template)
                ];
            }
        }

        return $wizardItems;
    }

    /**
     * @param Template $template
     *
     * @return array
     */
    private function getDsDefaultValues(Template $template)
    {
        $dsStructure = $template->getLocalDataprotArray();

        $dsValues = [
            'CType' => 'templavoila_pi1',
            'tx_templavoila_ds' => $template->getDatastructure()->getKey(),
            'tx_templavoila_to' => $template->getKey()
        ];

        if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
            foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
                $dsValues[$field] = $value;
            }
        }

        return $dsValues;
    }
}
