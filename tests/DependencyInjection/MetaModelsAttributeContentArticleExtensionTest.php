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
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */

namespace DependencyInjection;

use MetaModels\AttributeContentArticleBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension;
use MetaModels\AttributeContentArticleBundle\EventListener\BackendEventListener;
use MetaModels\AttributeContentArticleBundle\EventListener\GetOptionsListener;
use MetaModels\AttributeContentArticleBundle\EventListener\InitializeListener;
use MetaModels\AttributeContentArticleBundle\Table\ArticleContent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \MetaModels\AttributeContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension
 */
class MetaModelsAttributeContentArticleExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new MetaModelsAttributeContentArticleExtension();
        $extension->load([], $container);

        $expectedDefinitions = [
            'service_container',
            BackendEventListener::class,
            GetOptionsListener::class,
            InitializeListener::class,
            AttributeTypeFactory::class,
            ArticleContent::class,
        ];

        self::assertCount(count($expectedDefinitions), $container->getDefinitions());
        foreach ($expectedDefinitions as $expectedDefinition)
        {
            self::assertTrue($container->hasDefinition($expectedDefinition));
        }
    }
}
