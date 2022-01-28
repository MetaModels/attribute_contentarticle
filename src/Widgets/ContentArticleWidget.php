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
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\Widgets;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Contao\Widget;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ContaoBackendViewTemplate;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Widget\AbstractWidget;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ContentArticleWidget
 *
 * @package MetaModels\AttributeContentArticleBundle\Widgets
 */
class ContentArticleWidget extends AbstractWidget
{
    /**
     * Submit user input.
     *
     * @var boolean
     */
    protected $blnSubmitInput = false;

    /**
     * Add a for attribute.
     *
     * @var boolean
     */
    protected $blnForAttribute = false;

    /**
     * Template.
     *
     * @var string
     */
    protected $subTemplate = 'widget_contentarticle';

    /**
     * Flag if the current entry has an id.
     *
     * @var bool
     */
    protected $hasEmptyId = false;

    /**
     * The database connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * The contao input.
     *
     * @var \Contao\CoreBundle\Framework\Adapter|Input
     */
    private $input;

    /**
     * Check if we have an id, if not set a flag.
     * After this check call the parent constructor.
     *
     * @inheritDoc
     */
    public function __construct(
        $arrAttributes = null,
        DcCompat $dcCompat = null,
        Connection $connection = null,
        Adapter $input = null
    ) {
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

        if (null === $input) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Input adapter is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $input = System::getContainer()->get('contao.framework')->getAdapter(Input::class);
        }
        $this->input = $input;

        parent::__construct($arrAttributes, $dcCompat);

        $currentID        = $this->input->get('id');
        $this->hasEmptyId = empty($currentID);
    }

    /**
     * Set an object property
     *
     * @param string $strKey   The property name.
     * @param mixed  $varValue The property value.
     *
     * @return void
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'subTemplate':
                $this->subTemplate = $varValue;
                break;
            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    /**
     * Return an object property
     *
     * @param string $strKey The property name.
     *
     * @return string The property value
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'subTemplate':
                return $this->subTemplate;
            default:
        }

        return parent::__get($strKey);
    }

    /**
     * Check whether an object property exists
     *
     * @param string $strKey The property name.
     *
     * @return boolean True if the property exists
     */
    public function __isset($strKey)
    {
        switch ($strKey) {
            case 'subTemplate':
                return isset($this->subTemplate);
            default:
                return parent::__get($strKey);
        }
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string Generated String.
     *
     * @throws \Exception Throws Exceptions.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function generate()
    {
        $rootTable = $this->getRootMetaModelTable($this->strTable);

        $strQuery = http_build_query([
                                         'do'     => 'metamodel_' . ($rootTable ?: 'table_not_found'),
                                         'table'  => 'tl_content',
                                         'ptable' => $this->strTable,
                                         'id'     => $this->currentRecord,
                                         'mid'    => $this->currentRecord,
                                         'slot'   => $this->strName,
                                         'popup'  => 1,
                                         'nb'     => 1,
                                         'rt'     => REQUEST_TOKEN,
                                     ]);

        $contentElements = $this->getContentTypesByRecordId($this->currentRecord, $rootTable);

        $content = (new ContaoBackendViewTemplate($this->subTemplate))
            ->setTranslator($this->getEnvironment()->getTranslator())
            ->set('name', $this->strName)
            ->set('id', $this->strId)
            ->set('label', $this->label)
            ->set('readonly', $this->readonly)
            ->set('hasEmptyId', $this->hasEmptyId)
            ->set('link', 'contao?' . $strQuery)
            ->set('elements', $contentElements)
            ->parse();

//        return sprintf(
//            '<div><p><a href="%s" class="tl_submit" onclick="%s">%s</a></p></div>',
//            'contao?' . $strQuery,
//            'Backend.openModalIframe({width:850,title:\'' . $this->strLabel . '\',url:this.href});return false',
//            $edit
//        );

        return !Environment::get('isAjaxRequest') ? '<div>' . $content . '</div>' : $content;
    }

    /**
     * Get the RootMetaModelTable.
     *
     * @param string $strTable Table name to Check.
     *
     * @return bool|string Returns RootMetaModelTable.
     *
     * @throws \Exception Throws an Exception.
     */
    private function getRootMetaModelTable($strTable)
    {
        $arrTables = [];
        $objTables = \Database::getInstance()->execute(
            '
                SELECT tableName, d.renderType, d.ptable
                FROM tl_metamodel AS m
                JOIN tl_metamodel_dca AS d
                ON m.id = d.pid
        '
        );

        while ($objTables->next()) {
            $arrTables[$objTables->tableName] = [
                'renderType' => $objTables->renderType,
                'ptable'     => $objTables->ptable,
            ];
        }

        $getTable = function ($strTable) use (&$getTable, $arrTables) {
            if (!isset($arrTables[$strTable])) {
                return false;
            }

            $arrTable = $arrTables[$strTable];

            switch ($arrTable['renderType']) {
                case 'standalone':
                    return $strTable;

                case 'ctable':
                    return $getTable($arrTable['ptable']);

                default:
                    throw new \Exception('Unexpected case: ' . $arrTable['renderType']);
            }
        };

        return $getTable($strTable);
    }

    /**
     * in DB
     * pid    = $this->currentRecord
     * ptable = $this->getRootMetaModelTable($this->strTable)
     *
     * $GLOBALS['TL_LANG']['CTE']['metamodel_content']['0'] Bezeichnung
     */
    private function getContentTypesByRecordId($recordId, $ptableName)
    {
        $contentElements = [];

        if (empty($recordId) || empty($ptableName)) {
            return $contentElements;
        }

        $query    = sprintf(
            '
                SELECT cte.type, cte.invisible, cte.start, cte.stop
                FROM tl_content AS cte
                WHERE cte.pid = \'%s\' AND cte.ptable = \'%s\'
                ORDER BY cte.sorting
        ',
            $recordId,
            $ptableName
        );
        $elements = \Database::getInstance()->execute($query);

        while ($elements->next()) {
            $contentElements[] = [
                'name'      => $this->getEnvironment()->getTranslator()->translate($elements->type . '.0', 'CTE'),
                'isInvisible' => $elements->invisible
                               || ($elements->start && $elements->start > time())
                               || ($elements->stop && $elements->stop <= time())
            ];
        }

        return $contentElements;
    }
}
