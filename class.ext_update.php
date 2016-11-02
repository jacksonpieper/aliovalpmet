<?php
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

use Schnitzler\Templavoila\Traits\BackendUser;

/**
 * Update wizard for the extension manager
 */
class ext_update
{
    use BackendUser;

    /**
     * Main function, returning the HTML content of the module
     *
     * @return string HTML
     */
    public function main()
    {
        /** @var $dsWizard tx_templavoila_staticds_wizard */
        $dsWizard = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_templavoila_staticds_wizard::class);
        return $dsWizard->staticDsWizard();
    }

    /**
     * Checks if backend user is an administrator
     * (this function is called from the extension manager)
     *
     * @param string $what What should be updated
     *
     * @return bool
     */
    public function access($what = 'all')
    {
        return static::getBackendUser()->isAdmin();
    }
}
