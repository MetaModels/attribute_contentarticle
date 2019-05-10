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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ArticleContent extends \tl_content
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Save Data Container.
     *
     * @param \DataContainer $dataContainer The DC Driver.
     *
     * @return void
     */
    public function save(\DataContainer $dataContainer)
    {
        \Database::getInstance()
            ->prepare('UPDATE tl_content SET mm_slot=? WHERE id=?')
            ->execute(\Input::get('slot'), $dataContainer->id);
    }

    /**
     * Check permissions to edit table tl_content.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function checkPermission()
    {
        /** @var SessionInterface $objSession */
        $objSession = \System::getContainer()->get('session');

        // Prevent deleting referenced elements (see #4898)
        if (\Input::get('act') == 'deleteAll') {
            $objCes = \Database::getInstance()
                ->prepare("SELECT cteAlias 
                                    FROM tl_content 
                                    WHERE (ptable='tl_article' OR ptable='') 
                                      AND type='alias'")
                ->execute();

            $session                   = $objSession->all();
            $session['CURRENT']['IDS'] = array_diff($session['CURRENT']['IDS'], $objCes->fetchEach('cteAlias'));
            $objSession->replace($session);
        }

        if ($this->User->isAdmin) {
            return;
        }

        $strParentTable = \Input::get('ptable');
        $strParentTable = preg_replace('#[^A-Za-z0-9_]#', '', $strParentTable);

        // Check the current action
        switch (\Input::get('act')) {
            case 'paste':
                // Allow paste
                break;
            case '':
            case 'create':
            case 'select':
                // Check access to the article
                if (!$this->checkAccessToElement(CURRENT_ID, $strParentTable, true)) {
                    $this->redirect('contao?act=error');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                // Check access to the parent element if a content element is moved
                if ((\Input::get('act') == 'cutAll' ||
                     \Input::get('act') == 'copyAll') &&
                    !$this->checkAccessToElement(\Input::get('pid'), $strParentTable)) {
                    $this->redirect('contao?act=error');
                }

                $objCes = \Database::getInstance()->prepare('SELECT id FROM tl_content WHERE ptable=? AND pid=?')
                    ->execute($strParentTable, CURRENT_ID);

                $session                   = \Session::getInstance()->getData();
                $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objCes->fetchEach('id'));
                $objSession->replace($session);
                break;

            case 'cut':
            case 'copy':
                // Check access to the parent element if a content element is moved
                if (!$this->checkAccessToElement(\Input::get('pid'), $strParentTable)) {
                    $this->redirect('contao?act=error');
                }
            // NO BREAK STATEMENT HERE
            default:
                // Check access to the content element
                if (!$this->checkAccessToElement(\Input::get('id'), $strParentTable)) {
                    $this->redirect('contao?act=error');
                }
                break;
        }
    }

    /**
     * Check access to a particular content element.
     *
     * @param integer $accessId Check ID.
     * @param array   $ptable   Parent Table.
     * @param boolean $blnIsPid Is the ID a PID.
     *
     * @return bool
     */
    protected function checkAccessToElement($accessId, $ptable, $blnIsPid = false)
    {
        $strScript = \Environment::get('script');

        // Workaround for missing ptable when called via Page/File Picker
        if ($strScript != 'contao/page.php' && $strScript != 'contao/file.php') {
            if ($blnIsPid) {
                $objContent = \Database::getInstance()
                    ->prepare('SELECT 1 FROM `$ptable` WHERE id=?')
                    ->limit(1)
                    ->execute($accessId);
            } else {
                $objContent = \Database::getInstance()
                    ->prepare('SELECT 1 FROM tl_content WHERE id=? AND ptable=?')
                    ->limit(1)
                    ->execute($accessId, $ptable);
            }
        }

        // Invalid ID
        if ($objContent->numRows < 1) {
            $this->log('Invalid content element ID ' . $accessId, __METHOD__, TL_ERROR);

            return false;
        }

        return true;
    }
}
