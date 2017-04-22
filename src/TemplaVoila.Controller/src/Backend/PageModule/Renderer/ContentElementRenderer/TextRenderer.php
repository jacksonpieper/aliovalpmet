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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer;

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\AbstractContentElementRenderer;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Text controller
 */
class TextRenderer extends AbstractContentElementRenderer
{
    use LanguageService;

    /**
     * @var string
     */
    protected $previewField = 'bodytext';

    /**
     * @var mixed
     */
    protected $parentObj;

    /**
     * @return string
     */
    protected function getPreviewLabel()
    {
        return static::getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', $this->previewField));
    }

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        return $this->preparePreviewData($row[$this->previewField]);
    }

    /**
     * Performs a cleanup of the field values before they're passed into the preview
     *
     * @param string $str input usually taken from bodytext or any other field
     * @param int $max some items might not need to cover the full maximum
     * @param bool $stripTags HTML-blocks usually keep their tags
     *
     * @return string the properly prepared string
     */
    protected function preparePreviewData($str, $max = null, $stripTags = true)
    {
        //Enable to omit that parameter
        if ($max === null) {
            $max = 2000;
            if (isset($this->ref->modTSconfig['properties']['previewDataMaxLen'])) {
                $max = (int)$this->ref->modTSconfig['properties']['previewDataMaxLen'];
            }
        }

        $newStr = $str;
        if ($stripTags) {
            //remove tags but avoid that the output is concatinated without spaces (#8375)
            $newStr = strip_tags(preg_replace('/(\S)<\//', '\1 </', $str));
        }

        $wordLen = 75;
        if (isset($this->ref->modTSconfig['properties']['previewDataMaxWordLen'])) {
            $wordLen = (int)$this->ref->modTSconfig['properties']['previewDataMaxWordLen'];
        }

        if ($wordLen) {
            $newStr = preg_replace('/(\S{' . $wordLen . '})/', '\1 ', $newStr);
        }

        return htmlspecialchars(GeneralUtility::fixed_lgd_cs(trim($newStr), $max));
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
