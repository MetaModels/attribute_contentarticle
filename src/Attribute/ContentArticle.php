<?php

/**
 * This file is part of MetaModels/attribute_contentarticle.
 *
 * (c) 2012-2024 The MetaModels team.
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
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\Attribute;

use Contao\ContentModel;
use Contao\Controller;
use Contao\Model\Collection;
use Contao\System;
use MetaModels\Attribute\BaseComplex;
use MetaModels\AttributeContentArticleBundle\Widgets\ContentArticleWidget;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is the AttributeContentArticle class for handling article fields.
 */
class ContentArticle extends BaseComplex
{
    /**
     * Array of Call Ids.
     *
     * @var array
     */
    private static array $arrCallIds = [];

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = []): array
    {
        $arrFieldDef              = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType'] = 'contentarticle';

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        // Needed to fake implement BaseComplex.
        return [];
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
     * @return array<string, mixed>
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getDataFor($arrIds): array
    {
        $strTable       = $this->getMetaModel()->getTableName();
        $strColumn      = $this->getColName();
        $arrData        = [];
        $contentArticle = new ContentArticleWidget();

        foreach ($arrIds as $intId) {
            // Continue if it's a recursive call
            $strCallId = $strTable . '_' . $strColumn . '_' . $intId;
            if (isset(static::$arrCallIds[$strCallId])) {
                $arrData[$intId]['value'] = [\sprintf('RECURSION: %s', $strCallId)];
                continue;
            }
            static::$arrCallIds[$strCallId] = true;

            // Generate list for backend.
            $isBackend = (bool) System::getContainer()
                ->get('contao.routing.scope_matcher')
                ?->isBackendRequest(
                    System::getContainer()->get('request_stack')?->getCurrentRequest() ?? Request::create('')
                );
            if ($isBackend) {
                $rootTable = $contentArticle->getRootMetaModelTable($strTable);
                assert(\is_string($rootTable));
                $elements = $contentArticle->getContentTypesByRecordId($intId, $rootTable, $strColumn);
                $content  = '';
                if (\count($elements)) {
                    $content .= '<ul class="elements_container">';
                    foreach ($elements as $element) {
                        $content .= \sprintf(
                            '<li><div class="cte_type%s">' .
                            '<img src="system/themes/flexible/icons/%s.svg" width="16" height="16"> %s</div></li>',
                            $element['isInvisible'] ? ' unpublished' : ' published',
                            $element['isInvisible'] ? 'invisible' : 'visible',
                            $element['name']
                        );
                    }
                    $content .= '</ul>';
                }

                if (!empty($content)) {
                    $arrData[$intId]['value'] = [$content];
                } else {
                    $arrData[$intId]['value'] = [];
                }
            }

            // Generate output for frontend.
            $isFrontend = (bool) System::getContainer()
                ->get('contao.routing.scope_matcher')
                ?->isFrontendRequest(
                    System::getContainer()->get('request_stack')?->getCurrentRequest() ?? Request::create('')
                );
            if ($isFrontend) {
                $objContent = ContentModel::findPublishedByPidAndTable($intId, $strTable);
                $arrContent = [];

                if ($objContent !== null) {
                    assert($objContent instanceof Collection);
                    while ($objContent->next()) {
                        /** @psalm-suppress UndefinedMagicPropertyFetch */
                        if ($objContent->mm_slot === $strColumn) {
                            $arrContent[] = Controller::getContentElement($objContent->current());
                        }
                    }
                }

                if (!empty($arrContent)) {
                    $arrData[$intId]['value'] = $arrContent;
                } else {
                    $arrData[$intId]['value'] = [];
                }
            }

            unset(static::$arrCallIds[$strCallId]);
        }

        return $arrData;
    }
}
