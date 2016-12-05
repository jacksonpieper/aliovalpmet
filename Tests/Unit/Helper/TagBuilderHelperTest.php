<?php

namespace Schnitzler\Templavoila\Tests\Unit\Helper;

/**
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
use Schnitzler\Templavoila\Helper\TagBuilderHelper;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class Schnitzler\Templavoila\Tests\Unit\Helper\TagBuilderHelperTest
 */
class TagBuilderHelperTest extends UnitTestCase
{

    public function testGetCheckbox()
    {
        $tag = TagBuilderHelper::getCheckbox();
        $tag->render();

        static::assertSame(
            '<input type="checkbox" />',
            $tag->render()
        );
    }

    public function testOptionGroup()
    {
        $tag = TagBuilderHelper::getOptionGroup();
        $tag->render();

        static::assertSame(
            '<optgroup></optgroup>',
            $tag->render()
        );
    }

    public function testGetOption()
    {
        $tag = TagBuilderHelper::getOption();
        $tag->render();

        static::assertSame(
            '<option></option>',
            $tag->render()
        );
    }

    public function testGetHiddenField()
    {
        $tag = TagBuilderHelper::getHiddenField();
        $tag->render();

        static::assertSame(
            '<input type="hidden" />',
            $tag->render()
        );
    }

    public function testGetRadio()
    {
        $tag = TagBuilderHelper::getRadio();
        $tag->render();

        static::assertSame(
            '<input type="radio" />',
            $tag->render()
        );
    }

    public function testGetSelect()
    {
        $tag = TagBuilderHelper::getSelect();
        $tag->render();

        static::assertSame(
            '<select></select>',
            $tag->render()
        );
    }

    public function testGetTextarea()
    {
        $tag = TagBuilderHelper::getTextarea();
        $tag->render();

        static::assertSame(
            '<textarea></textarea>',
            $tag->render()
        );
    }

    public function testGetTextField()
    {
        $tag = TagBuilderHelper::getTextField();
        $tag->render();

        static::assertSame(
            '<input type="text" />',
            $tag->render()
        );
    }

    public function testGetSubmitButton()
    {
        $tag = TagBuilderHelper::getSubmitButton();
        $tag->render();

        static::assertSame(
            '<input type="submit" />',
            $tag->render()
        );
    }
}
