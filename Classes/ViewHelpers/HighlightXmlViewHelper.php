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
use Schnitzler\Templavoila\Service\SyntaxHighlightingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Class Schnitzler\Templavoila\ViewHelpers\HighlightXmlViewHelper
 */
class HighlightXmlViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @param string $xml
     * @param bool $useLineNumbers
     *
     * @throws InvalidVariableException
     * @throws InvalidArgumentException
     */
    public function render($xml, $useLineNumbers = true)
    {
        return static::renderStatic(
            [
                'xml' => $xml,
                'useLineNumbers' => $useLineNumbers
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
        $xml = $arguments['xml'];
        $useLineNumbers = (bool)$arguments['useLineNumbers'];

        if ((string)$xml === '') {
            throw new InvalidVariableException('An argument "xml" needs to be provided', 1477218307618);
        }

        $syntaxHighlightingService = GeneralUtility::makeInstance(SyntaxHighlightingService::class);

        if (strpos(substr($xml, 0, 100), '<T3DataStructure') !== false) {
            $title = 'Syntax highlighting <T3DataStructure> XML:';
            $formattedContent = $syntaxHighlightingService->highLight_DS($xml);
        } elseif (strpos(substr($xml, 0, 100), '<T3FlexForms') !== false) {
            $title = 'Syntax highlighting <T3FlexForms> XML:';
            $formattedContent = $syntaxHighlightingService->highLight_FF($xml);
        } else {
            $title = 'Unknown format:';
            $formattedContent = '<span style="font-style: italic; color: #666666;">' . htmlspecialchars($xml) . '</span>';
        }

        if ($useLineNumbers) {
            $lines = explode(chr(10), $formattedContent);
            foreach ($lines as $k => $v) {
                $lines[$k] = '<span style="color: black; font-weight:normal;">' . str_pad($k + 1, 4, ' ', STR_PAD_LEFT) . ':</span> ' . $v;
            }
            $formattedContent = implode(chr(10), $lines);
        }

        return '
            <h3>' . htmlspecialchars($title) . '</h3>
            <pre class="ts-hl">' . $formattedContent . '</pre>
            ';
    }
}
