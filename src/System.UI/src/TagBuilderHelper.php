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

namespace Schnitzler\System\UI;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class Schnitzler\System\UI\TagBuilderHelper
 */
final class TagBuilderHelper
{

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // deliberately private
    }

    /**
     * @return TagBuilder
     */
    public static function getCheckbox()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('input');
        $tag->addAttribute('type', 'checkbox');

        return $tag;
    }

    /**
     * @return TagBuilder
     */
    public static function getRadio()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('input');
        $tag->addAttribute('type', 'radio');

        return $tag;
    }

    /**
     * @return TagBuilder
     */
    public static function getSelect()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('select');
        $tag->forceClosingTag(true);

        return $tag;
    }

    /**
     * @return TagBuilder
     */
    public static function getOptionGroup()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('optgroup');
        $tag->forceClosingTag(true);

        return $tag;
    }

    /**
     * @return TagBuilder
     */
    public static function getOption()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('option');
        $tag->forceClosingTag(true);

        return $tag;
    }

    /**
     * @param string $name
     * @param string $class
     * @param string $content
     * @param int $rows
     * @return TagBuilder
     */
    public static function getTextarea()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('textarea');
        $tag->forceClosingTag(true);

        return $tag;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $class
     * @return TagBuilder
     */
    public static function getTextField()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('input');
        $tag->addAttribute('type', 'text');

        return $tag;
    }

    /**
     * @return TagBuilder
     */
    public static function getHiddenField()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('input');
        $tag->addAttribute('type', 'hidden');

        return $tag;
    }

    /**
     * @return TagBuilder
     */
    public static function getSubmitButton()
    {
        /** @var TagBuilder $tag */
        $tag = GeneralUtility::makeInstance(TagBuilder::class);
        $tag->setTagName('input');
        $tag->addAttribute('type', 'submit');

        return $tag;
    }
}
