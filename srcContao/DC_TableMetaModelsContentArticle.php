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
     * Creates the parent View.
     *
     * @return null|string|string[]
     */
    protected function parentView()
    {
        return preg_replace(
            [
                // "Edit parent" Button
                '#<div class="tl_header [^>]*>\n<div class="tl_content_right">\n<a #',
                // Parent entry info
                '#<td><span class="tl_label">tstamp:</span>.*\n.*</td>#',
            ],
            [
                '$0style="display:none" ',
                '<td>&nbsp;</td>',
            ],
            parent::parentView()
        );
    }
}
