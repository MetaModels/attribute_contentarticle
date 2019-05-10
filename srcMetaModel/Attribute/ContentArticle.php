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


namespace MetaModels\AttributeContentArticleBundle\Attribute;

use Contao\System;
use MetaModels\Attribute\BaseComplex;
use MetaModels\IMetaModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This is the AttributeContentArticle class for handling article fields.
 */
class ContentArticle extends BaseComplex
{
    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Array of Call Ids.
     *
     * @var array
     */
    private static $arrCallIds = [];

    /**
     * Create a new instance.
     *
     * @param IMetaModel               $objMetaModel The MetaModel instance this attribute belongs to.
     * @param array                    $arrData      The attribute information array.
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        IMetaModel $objMetaModel,
        $arrData = [],
        EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($objMetaModel, $arrData);

        if (null === $eventDispatcher) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @\trigger_error(
                'Event dispatcher is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $eventDispatcher = System::getContainer()->get('event_dispatcher');
        }

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDataType()
    {
        return 'varchar(255) NOT NULL default \'\'';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef              = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType'] = 'MetaModelAttributeContentArticle';

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        // Needed to fake implement BaseComplex.
    }

    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($arrIds)
    {
        // Needed to fake implement BaseComplex.
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFor($arrValues)
    {
        // Needed to fake implement BaseComplex.
    }

    /**
     * GetDataFor.
     *
     * @param array $arrIds Array of Data Ids.
     *
     * @return mixed[]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getDataFor($arrIds)
    {
        // Generate only for frontend (speeds up the backend a little)
        if (TL_MODE == 'BE') {
            return [];
        }

        $strTable  = $this->getMetaModel()->getTableName();
        $strColumn = $this->getColName();
        $arrData   = [];

        foreach ($arrIds as $intId) {
            // Continue if it's a recursive call
            $strCallId = $strTable . '_' . $strColumn . '_' . $intId;
            if (isset(static::$arrCallIds[$strCallId])) {
                $arrData[$intId]['value'] = sprintf('RECURSION: %s', $strCallId);
                continue;
            }
            static::$arrCallIds[$strCallId] = true;

            $objContent = \ContentModel::findPublishedByPidAndTable($intId, $strTable);
            $arrContent = [];

            if ($objContent !== null) {
                while ($objContent->next()) {
                    if ($objContent->mm_slot == $strColumn) {
                        $arrContent[] = $this->getContentElement($objContent->current());
                    }
                }
            }

            if (!empty($arrContent)) {
                $arrData[$intId]['value'] = $arrContent;
            } else {
                $arrData[$intId]['value'] = [];
            }

            unset(static::$arrCallIds[$strCallId]);
        }

        return $arrData;
    }
}
