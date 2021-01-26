<?php

declare(strict_types=1);

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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 */

namespace ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MetaModels\AttributeContentArticleBundle\ContaoManager\Plugin;
use MetaModels\AttributeContentArticleBundle\MetaModelsAttributeContentArticleBundle;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MetaModels\AttributeContentArticleBundle\ContaoManager\Plugin
 */
class PluginTest extends TestCase
{
    public function testGetBundles(): void
    {
        $parser = $this->createMock(ParserInterface::class);

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('getName')
            ->willReturn(MetaModelsAttributeContentArticleBundle::class);
        $config
            ->expects(self::once())
            ->method('getLoadAfter')
            ->willReturn([MetaModelsCoreBundle::class]);
        $config
            ->expects(self::once())
            ->method('getReplace')
            ->willReturn(['metamodelsattribute_article']);

        $plugin  = new Plugin();
        $bundles = $plugin->getBundles($parser);
        foreach ($bundles as $bundle) {
            self::assertSame($config->getName(), $bundle->getName());
            self::assertSame($config->getLoadAfter(), $bundle->getLoadAfter());
            self::assertSame($config->getReplace(), $bundle->getReplace());
        }
    }
}
