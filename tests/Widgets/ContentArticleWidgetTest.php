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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace Widgets;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeContentArticleBundle\Widgets\ContentArticleWidget;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \MetaModels\AttributeContentArticleBundle\Widgets\ContentArticleWidget
 */
class ContentArticleWidgetTest extends TestCase
{
    public function testNewInstance(): void
    {

        $dcCompat = $this->getMockBuilder(DcCompat::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $connection = $this->createMock(Connection::class);

        $input = $this->getMockBuilder(Adapter::class)
                      ->disableOriginalConstructor()
                      ->addMethods(['get'])
                      ->getMock();

        $input
            ->expects(self::once())
            ->method('get')
            ->willReturn(1);

        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();

        $widget = $this->getMockBuilder(ContentArticleWidget::class)
                       ->setConstructorArgs([null, $dcCompat, $connection, $input, $translator])
                       ->onlyMethods(['import'])
                       ->getMock();

        $widget
            ->expects(self::any())
            ->method('import')
            ->willReturnCallback(function ($parameters) {
                $this->assertInstanceof(Config::class, $parameters[0]);
                $this->assertSame('Config', $parameters[1]);
            });

        self::assertEmpty($widget->getAttributes());
    }
}
