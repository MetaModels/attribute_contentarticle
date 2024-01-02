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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2023 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeContentArticleBundle\Test;

use MetaModels\AttributeContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension;
use MetaModels\AttributeContentArticleBundle\MetaModelsAttributeContentArticleBundle;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MetaModels\AttributeContentArticleBundle\MetaModelsAttributeContentArticleBundle
 *
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class MetaModelsAttributeContentArticleBundleTest extends TestCase
{
    public function testNewInstance(): void
    {
        $bundle = new MetaModelsAttributeContentArticleBundle();

        self::assertInstanceOf(MetaModelsAttributeContentArticleExtension::class, $bundle->getContainerExtension());
    }
}
