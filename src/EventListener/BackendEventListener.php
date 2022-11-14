<?php

/**
 * This file is part of MetaModels/attribute_contentarticle.
 *
 * (c) 2012-2022 The MetaModels team.
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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\EventListener;

use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostDuplicateModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostPasteModelEvent;
use Doctrine\DBAL\Connection;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Data\Driver;

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
     * The database connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * The ArticleContent constructor.
     *
     * @param Connection|null $connection The database connection.
     */
    public function __construct(Connection $connection = null)
    {
        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
        }
        $this->connection = $connection;
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
        $objContent = $this->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('tl_content', 't')
            ->where('t.pid=:id')
            ->andWhere('t.ptable=:ptable')
            ->setParameter('id', $intSourceId)
            ->setParameter('ptable', $strTable)
            ->execute();

        while ($row = $objContent->fetch(\PDO::FETCH_ASSOC)) {
            $arrContent        = $row;
            $arrContent['pid'] = $intDestinationId;
            unset($arrContent['id']);

            $this->connection
                ->createQueryBuilder()
                ->insert('tl_content')
                ->setParameters($arrContent)
                ->execute();
        }
    }
}
