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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\Controller\Backend;

use Contao\Ajax;
use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\EditableDataContainerInterface;
use Contao\Environment;
use Contao\ListableDataContainerInterface;
use Contao\StringUtil;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Factory\DcGeneralFactoryService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MetaModels\AttributeContentArticleBundle\Table\ArticleContent;
use MetaModels\ViewCombination\ViewCombination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

/**
 * Class MetaModelController
 *
 * @package MetaModels\AttributeContentArticleBundle\Controller\Backend
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class MetaModelController
{
    /** @psalm-suppress UndefinedMagicPropertyAssignment */
    public function __invoke(
        string $tableName,
        string $attribute,
        string $itemId,
        Request $request,
        Connection $connection,
    ): Response {
        if ('' === $tableName || '' === $attribute || '' === $itemId) {
            throw new BadRequestHttpException();
        }

        $ajax = null;
        if ((0 < $request->request->count()) && $request->isXmlHttpRequest()) {
            $ajax = new Ajax((string) $request->request->get('action'));
            $ajax->executePreActions();
        }

        $template           = new BackendTemplate('be_main');
        $template->headline = $attribute;
        $template->title    = StringUtil::specialchars(strip_tags($template->headline));
        // Load the language and DCA file
        System::loadLanguageFile('tl_content');
        Controller::loadDataContainer('tl_content');

        $template->theme    = Backend::getTheme();
        $template->base     = Environment::get('base');
        $template->language = System::getContainer()->get('request_stack')?->getCurrentRequest()?->getLocale();
        $template->host     = Backend::getDecodedHostname();
        $template->charset  = System::getContainer()->getParameter('kernel.charset');
        $template->isPopup  = true;

        $session = $request->getSession();
        assert($session instanceof SessionInterface);

        $session->set('CURRENT_ID', $itemId);
        // Define the current ID
        \define('CURRENT_ID', $itemId);

        $security = System::getContainer()->get('security.helper');
        assert($security instanceof Security);

        // Include all excluded fields which are allowed for the current user
        if (\is_array($GLOBALS['TL_DCA']['tl_content']['fields'] ?? null)) {
            foreach ($GLOBALS['TL_DCA']['tl_content']['fields'] as $k => $v) {
                if (
                    (null !== ($v['exclude'] ?? null))
                    && $security->isGranted(
                        ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE,
                        'tl_content::' . $k
                    )
                ) {
                    $GLOBALS['TL_DCA']['tl_content']['fields'][$k]['exclude'] = false;
                }
            }
        }

        // Fabricate a new data container object
        if (!isset($GLOBALS['TL_DCA']['tl_content']['config']['dataContainer'])) {
            System::getContainer()->get('monolog.logger.contao.error')?->error(
                'Missing data container for table "tl_content"'
            );
            trigger_error('Could not create a data container object', E_USER_ERROR);
        }
        $action = (string) $request->query->get('act');
        $this->fixDca($action, $tableName, $attribute, $itemId, $connection, $session, $security);

        // HACK: DC_Table expects these:
        $_GET['table'] = 'tl_content';

        /** @var class-string $driverClass */
        $driverClass = DataContainer::getDriverForTable('tl_content');
        /** @var DataContainer $dataContainer */
        $dataContainer         = new $driverClass('tl_content', []);
        $dataContainer->ptable = $tableName;

        // Wrap the existing headline
        $template->headline = '<span>' . $template->headline . '</span>';

        // AJAX request
        if ($ajax instanceof Ajax) {
            $ajax->executePostActions($dataContainer);
        } else {
            if (!$action || $action === 'paste' || $action === 'select') {
                $action = ($dataContainer instanceof ListableDataContainerInterface) ? 'showAll' : 'edit';
            }
            switch ($action) {
                case 'delete':
                case 'show':
                case 'showAll':
                case 'undo':
                    if (!$dataContainer instanceof ListableDataContainerInterface) {
                        System::getContainer()->get('monolog.logger.contao.error')?->error(
                            'Data container tl_content is not listable'
                        );
                        trigger_error('The current data container is not listable', E_USER_ERROR);
                    }
                    break;

                case 'create':
                case 'cut':
                case 'cutAll':
                case 'copy':
                case 'copyAll':
                case 'move':
                case 'edit':
                case 'editAll':
                case 'toggle':
                    if (!$dataContainer instanceof EditableDataContainerInterface) {
                        System::getContainer()->get('monolog.logger.contao.error')?->error(
                            'Data container tl_content is not editable'
                        );
                        trigger_error('The current data container is not editable', E_USER_ERROR);
                    }
                    break;
            }

            $template->main = $dataContainer->$action();
        }

        return $template->getResponse();
    }

    private function fixDca(
        string $action,
        string $parent,
        string $attribute,
        string $itemId,
        Connection $connection,
        Session $session,
        Security $security
    ): void {
        $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] =
            function (DataContainer $dataContainer) use ($parent, $itemId, $attribute, $connection): void {
                $this->updateContentElement($connection, $itemId, $parent, $attribute, (string) $dataContainer->id);
            };
        $GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][]   =
            $this->slotSetterCallback($parent, $attribute, $itemId, $connection);
        $GLOBALS['TL_DCA']['tl_content']['config']['oncut_callback'][]    =
            $this->slotSetterCallback($parent, $attribute, $itemId, $connection);

        $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] =
            $this->checkPermission($action, $parent, $itemId, $connection, $session, $security);

        $GLOBALS['TL_DCA']['tl_content']['list']['operations']['toggle']['button_callback'] = $this->toggleIcon();
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['filter'][]                     = ['mm_slot=?', $attribute];
    }

    /**
     * Setter callback.
     *
     * @param string     $parent
     * @param string     $attribute
     * @param string     $itemId
     * @param Connection $connection
     *
     * @return callable
     */
    private function slotSetterCallback(
        string $parent,
        string $attribute,
        string $itemId,
        Connection $connection
    ): callable {
        return function (string $insertId) use ($parent, $itemId, $attribute, $connection): void {
            $this->updateContentElement($connection, $itemId, $parent, $attribute, $insertId);
        };
    }


    /**
     * Check permissions to edit table tl_content.
     *
     * @param string     $action
     * @param string     $parent
     * @param string     $itemId
     * @param Connection $connection
     * @param Session    $session
     * @param Security   $security
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function checkPermission(
        string $action,
        string $parent,
        string $itemId,
        Connection $connection,
        Session $session,
        Security $security
    ): callable {
        return static function () use ($action, $parent, $itemId, $connection, $session, $security): void {
            if ($security->isGranted('ROLE_ADMIN')) {
                return;
            }

            // Check the current action.
            switch ($action) {
                case 'editAll':
                case 'deleteAll':
                case 'overrideAll':
                case 'cutAll':
                case 'copyAll':
                    /** @psalm-suppress UndefinedMagicPropertyFetch */
                    $objCes = $connection
                        ->createQueryBuilder()
                        ->select('t.id')
                        ->from('tl_content', 't')
                        ->where('t.ptable=:parentTable')
                        ->andWhere('t.pid=:currentId')
                        ->setParameter('parentTable', $parent)
                        ->setParameter('currentId', $itemId)
                        ->executeQuery();

                    $contaoBeSession = $session->getBag('contao_backend');
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
        };
    }

    /**
     * Update content element.
     *
     * @param Connection $connection
     * @param string     $itemId
     * @param string     $parent
     * @param string     $attribute
     * @param string     $insertId
     *
     * @return void
     * @throws Exception
     */
    private function updateContentElement(
        Connection $connection,
        string $itemId,
        string $parent,
        string $attribute,
        string $insertId
    ): void {
        $connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.pid', ':pid')
            ->set('t.ptable', ':ptable')
            ->set('t.mm_slot', ':slot')
            ->where('t.id=:id')
            ->setParameter('pid', $itemId)
            ->setParameter('ptable', $parent)
            ->setParameter('slot', $attribute)
            ->setParameter('id', $insertId)
            ->executeQuery();
    }

    private function toggleIcon(): callable
    {
        return static function (): string {
            /** @psalm-suppress UndefinedClass */
            static $controller = new \tl_content();
            return \call_user_func_array([$controller, 'toggleIcon'], \func_get_args());
        };
    }
}
