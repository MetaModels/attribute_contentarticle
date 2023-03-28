<?php

/**
 * This file is part of MetaModels/attribute_contentarticle.
 *
 * (c) 2012-2023 The MetaModels team.
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
 * @author     Marc Reimann <reimann@mediendepot-ruhr.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2023 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\Table;

use Contao\Backend;
use Contao\BackendUser;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * This is a DCA helper class.
 */
class ArticleContent
{
    /**
     * The database connection.
     *
     * @var Connection|null
     */
    private Connection|null $connection;

    /**
     * Symfony session object
     *
     * @var Session
     */
    private Session $session;

    /**
     * The ArticleContent constructor.
     *
     * @param Connection|null $connection The database connection.
     * @param Session|null    $session    The session.
     */
    public function __construct(Connection $connection = null, Session $session = null)
    {
        if (null === ($this->connection = $connection)) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $this->connection = System::getContainer()->get('database_connection');
        }

        if (null === $session) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Not passing an "Session" is deprecated.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $session = System::getContainer()->get('session');
            assert($session instanceof Session);
            $this->session = $session;
        }
    }

    /**
     * Return the "toggle visibility" button
     *
     * @return string The icon url with all information.
     */
    public function toggleIcon(): string
    {
        $controller = new \tl_content();

        return \call_user_func_array([$controller, 'toggleIcon'], \func_get_args());
    }

    /**
     * Save Data Container.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     * @throws Exception
     */
    public function save(DataContainer $dataContainer): void
    {
        $this->connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.mm_slot', ':slot')
            ->where('t.id=:id')
            ->setParameter('slot', Input::get('slot'))
            ->setParameter('id', $dataContainer->id)
            ->executeQuery();
    }

    /**
     * Update the data from copies and set the context like pid, parent table, slot.
     *
     * @param string        $insertId      The id of the new entry.
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function updateCopyData(string $insertId, DataContainer $dataContainer): void
    {
        $pid    = Input::get('mid');
        $ptable = Input::get('ptable');
        $slot   = Input::get('slot');

        if (empty($pid) || empty($ptable) || empty($slot)) {
            $errorCode  = 'Could not update row because one of the data are missing. ';
            $errorCode .= 'Insert ID: %s, Pid: %s, Parent table: %s, Slot: %s';
            throw new \RuntimeException(
                \sprintf(
                    $errorCode,
                    $insertId,
                    $pid,
                    $ptable,
                    $slot
                )
            );
        }

        $this->connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.pid', ':pid')
            ->set('t.ptable', ':ptable')
            ->set('t.mm_slot', ':slot')
            ->where('t.id=:id')
            ->setParameter('pid', $pid)
            ->setParameter('ptable', $ptable)
            ->setParameter('slot', $slot)
            ->setParameter('id', $insertId)
            ->executeQuery();
    }

    /**
     * Update the data from copies and set the context like pid, parent table, slot.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws Exception
     */
    public function updateCutData(DataContainer $dataContainer): void
    {
        $pid      = Input::get('mid');
        $ptable   = Input::get('ptable');
        $slot     = Input::get('slot');
        $insertId = $dataContainer->id;

        if (empty($pid) || empty($ptable) || empty($slot)) {
            $errorCode  = 'Could not update row because one of the data are missing. ';
            $errorCode .= 'Insert ID: %s, Pid: %s, Parent table: %s, Slot: %s';
            throw new \RuntimeException(
                \sprintf(
                    $errorCode,
                    $insertId,
                    $pid,
                    $ptable,
                    $slot
                )
            );
        }

        $this->connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.pid', ':pid')
            ->set('t.ptable', ':ptable')
            ->set('t.mm_slot', ':slot')
            ->where('t.id=:id')
            ->setParameter('pid', $pid)
            ->setParameter('ptable', $ptable)
            ->setParameter('slot', $slot)
            ->setParameter('id', $insertId)
            ->executeQuery();
    }

    /**
     * Check permissions to edit table tl_content.
     *
     * @param DataContainer $dataContainer The data container.
     *
     * @return void
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    public function checkPermission(DataContainer $dataContainer): void
    {
        if (BackendUser::getInstance()->isAdmin) {
            return;
        }

        $strParentTable = Input::get('ptable');
        $strParentTable = \preg_replace('#[^A-Za-z0-9_]#', '', $strParentTable);

        // Check the current action
        switch (Input::get('act')) {
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                $objCes = $this->connection
                    ->createQueryBuilder()
                    ->select('t.id')
                    ->from('tl_content', 't')
                    ->where('t.ptable=:parentTable')
                    ->andWhere('t.pid=:currentId')
                    ->setParameter('parentTable', $strParentTable)
                    ->setParameter('currentId', $dataContainer->currentPid)
                    ->executeQuery();
                $contaoBeSession = $this->session->getBag('contao_backend');
                assert($contaoBeSession instanceof AttributeBagInterface);
                $contaoBeSession->set(
                    'CURRENT',
                    \array_diff($contaoBeSession->get('CURRENT') ?? [], $objCes->fetchFirstColumn())
                );
                break;
            case 'paste':
            case '':
            case 'create':
            case 'select':
            case 'cut':
            case 'copy':
            default:
        }
    }
}
