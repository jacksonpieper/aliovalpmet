<?php
declare(strict_types = 1);

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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer;

use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\AbstractContentElementRenderer;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Media controller
 */
class MediaRenderer extends AbstractContentElementRenderer
{
    use LanguageService;

    /**
     * @var string
     */
    protected $previewField = 'media';

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        $data = '';
        if (is_array($row) && $row['pi_flexform']) {
            $flexform = GeneralUtility::xml2array($row['pi_flexform']);
            if (isset($flexform['data']['sDEF']['lDEF']['mmFile']['vDEF'])) {
                $data = '<span>' . $flexform['data']['sDEF']['lDEF']['mmFile']['vDEF'] . '</span>';
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getPreviewLabel()
    {
        return static::getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', 'CType', $this->previewField));
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $label = $this->getPreviewLabel();
        $data = $this->getPreviewData($this->row);

        if ($this->ref->currentElementBelongsToCurrentPage) {
            return $this->ref->link_edit('<strong>' . $label . '</strong> ' . $data, 'tt_content', $this->row['uid']);
        } else {
            return '<strong>' . $label . '</strong> ' . $data;
        }
    }
}
