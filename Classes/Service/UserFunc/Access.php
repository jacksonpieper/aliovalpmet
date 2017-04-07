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

namespace Schnitzler\Templavoila\Service\UserFunc;

use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class being included by UserAuthGroup using a hook
 *
 *
 */
class Access
{
    use BackendUser;
    use LanguageService;

    /**
     * Checks if user is allowed to modify FCE.
     *
     * @param array $params Parameters
     * @param object $ref Parent object
     *
     * @return bool <code>true</code> if change is allowed
     */
    public function recordEditAccessInternals($params, $ref)
    {
        if ($params['table'] === 'tt_content' && is_array($params['idOrRow']) && $params['idOrRow']['CType'] === 'templavoila_pi1') {
            if (!$ref) {
                $user = & static::getBackendUser();
            } else {
                $user = & $ref;
            }
            if ($user->isAdmin()) {
                return true;
            }

            if (!$this->checkObjectAccess('tx_templavoila_datastructure', $params['idOrRow']['tx_templavoila_ds'], $ref)) {
                $error = 'access_noDSaccess';
            } elseif (!$this->checkObjectAccess('tx_templavoila_tmplobj', $params['idOrRow']['tx_templavoila_to'], $ref)) {
                $error = 'access_noTOaccess';
            } else {
                return true;
            }
            if ($ref) {
                static::getLanguageService()->init($user->uc['lang']);
                $ref->errorMsg = static::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang_access.xlf:' . $error);
            }

            return false;
        }

        return true;
    }

    /**
     * Checks user's access to given database object
     *
     * @param string $table Table name
     * @param int $uid UID of the record
     * @param BackendUserAuthentication $be_user BE user object
     *
     * @return bool <code>true</code> if access is allowed
     */
    public function checkObjectAccess($table, $uid, $be_user)
    {
        if (!$be_user) {
            $be_user = static::getBackendUser();
        }
        if (!$be_user->isAdmin()) {
            $prefLen = strlen($table) + 1;
            foreach ($be_user->userGroups as $group) {
                $items = GeneralUtility::trimExplode(',', $group['tx_templavoila_access'], true);
                foreach ($items as $ref) {
                    if (strstr($ref, $table)) {
                        if ($uid == (int)substr($ref, $prefLen)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }
}
