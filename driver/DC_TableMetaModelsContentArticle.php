<?php

/**
 * This file is part of MetaModels/attribute_contentarticle.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeContentArticle
 * @author     Andreas Dziemba <adziemba@web.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace Contao;

/**
 * Class DC_TableMetaModelsContentArticle
 *
 * @package Contao
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class DC_TableMetaModelsContentArticle extends DC_Table
{
    /**
     * Remove some elements from the basic class.
     *
     * @inheritDoc
     */
    protected function parentView()
    {
        return preg_replace(
            [
                // Remove the "Edit parent" Button (see: \Contao\DC_Table::parentView).
                '/(<div class="tl_header [^>]*>.+<div class="tl_content_right">.+)' .
                '<a.+class="edit"[^>]+>.*?(?=<\/a>)<\/a>' .
                '(.+<\/div>)/s',
                // Remove the parent entry info.
                '#<td><span class="tl_label">tstamp:</span>.*\n.*</td>#',
            ],
            [
                '$1$2',
                '<td>&nbsp;</td>',
            ],
            parent::parentView()
        );
    }
}
