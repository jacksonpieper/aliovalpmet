<?php

use Extension\Templavoila\Controller\FrontendController;
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
        GeneralUtility::deprecationLog('"userFunc = tx_templavoila_pi1->main_page" is deprecated, use "userFunc = Extension\Templavoila\Controller\FrontendController->renderPage" instead');
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
        GeneralUtility::deprecationLog('"userFunc = tx_templavoila_pi1->main_record" is deprecated, use "userFunc = Extension\Templavoila\Controller\FrontendController->renderRecord" instead');
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
        GeneralUtility::deprecationLog('"userFunc = tx_templavoila_pi1->tvSectionIndex" is deprecated, use "userFunc = Extension\Templavoila\Controller\FrontendController->renderSectionIndex" instead');
        return parent::renderSectionIndex($content, $conf);
    }
}
