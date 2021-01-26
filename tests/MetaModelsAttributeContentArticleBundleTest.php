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

use MetaModels\AttributeContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension;
use MetaModels\AttributeContentArticleBundle\MetaModelsAttributeContentArticleBundle;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MetaModels\AttributeContentArticleBundle\MetaModelsAttributeContentArticleBundle
 */
class MetaModelsAttributeContentArticleBundleTest extends TestCase
{
    public function testNewInstance(): void
    {
        $bundle = new MetaModelsAttributeContentArticleBundle();

        self::assertInstanceOf(MetaModelsAttributeContentArticleExtension::class, $bundle->getContainerExtension());
    }
}
