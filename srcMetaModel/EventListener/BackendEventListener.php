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

namespace MetaModels\AttributeContentArticleBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostDuplicateModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostPasteModelEvent;
use MetaModels\DcGeneral\Data\Driver;
use MetaModels\DcGeneral\Data\Model;

/**
 * Handles event operations on tl_metamodel_dcasetting.
 */
class BackendEventListener
{
    /**
     * DuplicationSourceID.
     *
     * @var int
     */
    private $intDuplicationSourceId;

    /**
     * Set the language for the widget.
     *
     * @param ManipulateWidgetEvent $event The event.
     *
     * @return void
     */
    public function setWidgetLanguage(ManipulateWidgetEvent $event)
    {

        if ($event->getWidget()->type != 'article') {
            return;
        }

        /** @var Driver $dataProvider */
        $dataProvider = $event->getEnvironment()->getDataProvider($event->getModel()->getProviderName());
        $language     = $dataProvider->getCurrentLanguage() ?: '-';

        $event->getWidget()->lang = $language;
    }


    /**
     * Handle Post Duplication Model.
     *
     * @param PostDuplicateModelEvent $event The event.
     *
     * @return void
     */
    public function handlePostDuplicationModel(PostDuplicateModelEvent $event)
    {
        /** @var Model $objSourceModel */
        $objSourceModel = $event->getSourceModel();

        /** @var Model $objDestinationModel */
        $objDestinationModel = $event->getModel();

        $strTable         = $objDestinationModel->getProviderName();
        $intSourceId      = $objSourceModel->getId();
        $intDestinationId = $objDestinationModel->getId();

        if ($intDestinationId) {
            $this->duplicateContentEntries($strTable, $intSourceId, $intDestinationId);
        } else {
            $this->intDuplicationSourceId = $intSourceId;
        }
    }


    /**
     * HandlePostPasteModel Event Listener.
     *
     * @param PostPasteModelEvent $event The event.
     *
     * @return void
     */
    public function handlePostPasteModel(PostPasteModelEvent $event)
    {
        if (!$this->intDuplicationSourceId) {
            return;
        }

        /** @var Model $objDestinationModel */
        $objDestinationModel = $event->getModel();

        $strTable         = $objDestinationModel->getProviderName();
        $intSourceId      = $this->intDuplicationSourceId;
        $intDestinationId = $objDestinationModel->getId();

        $this->duplicateContentEntries($strTable, $intSourceId, $intDestinationId);
    }


    /**
     * Duplicate the content entries.
     *
     * @param string $strTable         Table.
     *
     * @param int    $intSourceId      The Source Id.
     *
     * @param int    $intDestinationId The Destination Id.
     *
     * @return void
     */
    private function duplicateContentEntries($strTable, $intSourceId, $intDestinationId)
    {
        $objContent = \Database::getInstance()
            ->prepare('SELECT * FROM tl_content WHERE pid=? AND ptable=?')
            ->execute($intSourceId, $strTable);

        while ($objContent->next()) {
            $arrContent        = $objContent->row();
            $arrContent['pid'] = $intDestinationId;
            unset($arrContent['id']);

            \Database::getInstance()
                ->prepare('INSERT INTO tl_content %s')
                ->set($arrContent)
                ->execute();
        }
    }
}
