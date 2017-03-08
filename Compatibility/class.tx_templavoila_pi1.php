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

use Schnitzler\Templavoila\Controller\FrontendController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class tx_templavoila_pi1
 */
class tx_templavoila_pi1 extends FrontendController
{
    /**
     * @param string $content
     * @param array $conf
     *
     * @return string
     *
     * @deprecated since 7.6.0, will be removed in 8.0.0
     */
    public function main_page($content, $conf)
    {
        GeneralUtility::deprecationLog('"userFunc = tx_templavoila_pi1->main_page" is deprecated, use "userFunc = Schnitzler\Templavoila\Controller\FrontendController->renderPage" instead');
        return parent::renderPage($content, $conf);
    }

    /**
     * @param string $content
     * @param array $conf
     *
     * @return string
     *
     * @deprecated since 7.6.0, will be removed in 8.0.0
     */
    public function main_record($content, $conf)
    {
        GeneralUtility::deprecationLog('"userFunc = tx_templavoila_pi1->main_record" is deprecated, use "userFunc = Schnitzler\Templavoila\Controller\FrontendController->renderRecord" instead');
        return parent::renderRecord($content, $conf);
    }

    /**
     * @param string $content
     * @param array $conf
     *
     * @return string
     *
     * @deprecated since 7.6.0, will be removed in 8.0.0
     */
    public function tvSectionIndex($content, $conf)
    {
        GeneralUtility::deprecationLog('"userFunc = tx_templavoila_pi1->tvSectionIndex" is deprecated, use "userFunc = Schnitzler\Templavoila\Controller\FrontendController->renderSectionIndex" instead');
        return parent::renderSectionIndex($content, $conf);
    }
}
