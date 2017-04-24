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

namespace Schnitzler\System\UI\Tests\Unit;

use Schnitzler\System\UI\TagBuilderHelper;
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
        $tag = \Schnitzler\System\UI\TagBuilderHelper::getSubmitButton();
        $tag->render();

        static::assertSame(
            '<input type="submit" />',
            $tag->render()
        );
    }
}
