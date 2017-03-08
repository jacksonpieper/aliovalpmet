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

namespace Schnitzler\Templavoila\ViewHelpers;

use InvalidArgumentException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class Schnitzler\Templavoila\ViewHelpers\LlViewHelper
 */
class LlViewHelper extends AbstractViewHelper implements CompilableInterface
{

    /**
     * @var LanguageService
     */
    protected static $languageService;

    /**
     * @throws InvalidArgumentException
     *
     * @return LanguageService
     */
    protected static function getLanguageService()
    {
        if (!static::$languageService instanceof LanguageService) {
            static::$languageService = GeneralUtility::makeInstance(LanguageService::class);
        }

        return static::$languageService;
    }

    /**
     * @param string $index
     * @param mixed $arguments
     *
     * @throws InvalidVariableException
     * @throws InvalidArgumentException
     */
    public function render($index = null, $arguments = null)
    {
        return static::renderStatic(
            [
                'index' => $index,
                'arguments' => $arguments
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @throws InvalidVariableException
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $index = $arguments['index'];
        $arguments = $arguments['arguments'];

        if ((string)$index === '') {
            throw new InvalidVariableException('An argument "index" needs to be provided', 1467720203023);
        }

        $value = sprintf(static::getLanguageService()->getLL($index), $arguments);
        return $value ?: 'LL:' . $index;
    }
}
