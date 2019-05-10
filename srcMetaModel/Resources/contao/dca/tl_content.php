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

$GLOBALS['TL_DCA']['tl_content']['fields']['mm_slot'] = [
    'sql' => "varchar(255) NOT NULL default ''",
];

$strModule = \Input::get('do');
$strTable  = \Input::get('table');

// Change TL_Content for the article popup
if (\substr($strModule, 0, 10) == 'metamodel_' && $strTable == 'tl_content') {
    $GLOBALS['TL_DCA']['tl_content']['config']['dataContainer']                         =
        'TableMetaModelsContentArticle';
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable']                                =
        \Input::get('ptable');
    $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][]                   =
        array(
            'MetaModels\\AttributeContentArticleBundle\\Table\\ArticleContent',
            'save'
        );
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][]                     =
        array(
            'MetaModels\\AttributeContentArticleBundle\\Table\\ArticleContent',
            'checkPermission'
        );
    $GLOBALS['TL_DCA']['tl_content']['list']['operations']['toggle']['button_callback'] =
        array(
            'MetaModels\\AttributeContentArticleBundle\\Table\\ArticleContent',
            'toggleIcon'
        );
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['filter'][]                     =
        array(
            'mm_slot=?',
            \Input::get('slot')
        );
}
