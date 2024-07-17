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

namespace DependencyInjection;

use MetaModels\AttributeContentArticleBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeContentArticleBundle\Controller\Backend\MetaModelController;
use MetaModels\AttributeContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension;
use MetaModels\AttributeContentArticleBundle\EventListener\BackendEventListener;
use MetaModels\AttributeContentArticleBundle\EventListener\GetOptionsListener;
use MetaModels\AttributeContentArticleBundle\EventListener\InitializeListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \MetaModels\AttributeContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension
 *
 * @SuppressWarnings(PHPMD.LongClassName)
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
            MetaModelController::class,
        ];

        self::assertCount(count($expectedDefinitions), $container->getDefinitions());
        foreach ($expectedDefinitions as $expectedDefinition) {
            self::assertTrue($container->hasDefinition($expectedDefinition));
        }
    }
}
