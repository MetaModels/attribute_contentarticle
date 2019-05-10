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

namespace MetaModels\AttributeContentArticleBundle\Table;

use Contao\Backend;

/**
 * Class MetaModelAttributeContentArticle.
 *
 * @package MetaModels\AttributeContentArticleBundle\Table
 */
class MetaModelAttributeContentArticle extends Backend
{
    /**
     * Main Language Content.
     *
     * @param \DataContainer $dataContainer The DC Driver.
     *
     * @return void
     */
    public function addMainLangContent($dataContainer)
    {
        $factory = \System::getContainer()->get('metamodels.factory');
        /** @var \MetaModels\IFactory $factory */
        $objMetaModel = $factory->getMetaModel($dataContainer->parentTable);

        $intId           = $dataContainer->id;
        $strParentTable  = $dataContainer->parentTable;
        $strSlot         = \Input::get('slot');
        $strLanguage     = \Input::get('lang');
        $strMainLanguage = $objMetaModel->getFallbackLanguage();

        // To DO Message::addError übersetzen
        if ($strLanguage == $strMainLanguage) {
            \Message::addError('Hauptsprache kann nicht in die Hauptsprache kopiert werden.');
            \Controller::redirect(\System::getReferer());

            return;
        }

        $objContent = \Database::getInstance()
            ->prepare('SELECT * FROM tl_content WHERE pid=? AND ptable=? AND mm_slot=? AND mm_lang=?')
            ->execute($intId, $strParentTable, $strSlot, $strMainLanguage);

        $counter = 0;
        while ($objContent->next()) {
            $arrContent            = $objContent->row();
            $arrContent['mm_lang'] = $strLanguage;
            unset($arrContent['id']);

            \Database::getInstance()
                ->prepare('INSERT INTO tl_content %s')
                ->set($arrContent)
                ->execute();
            $counter++;
        }

        // TO DO Message::addError übersetzen
        \Message::addInfo(sprintf('%s Element(e) kopiert', $counter));
        \Controller::redirect(\System::getReferer());
    }
}
