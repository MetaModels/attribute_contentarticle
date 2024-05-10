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
 * @author     Andreas Dziemba <adziemba@web.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\Attribute;

use MetaModels\Attribute\AbstractSimpleAttributeTypeFactory;
use Doctrine\DBAL\Connection;
use MetaModels\Helper\TableManipulator;

/**
 * Attribute type factory for article attributes.
 */
class AttributeTypeFactory extends AbstractSimpleAttributeTypeFactory
{
    /**
     * {@inheritDoc}
     */
    public function __construct(
        Connection $connection,
        TableManipulator $tableManipulator
    ) {
        parent::__construct($connection, $tableManipulator);

        $this->typeName  = 'contentarticle';
        $this->typeIcon  = 'bundles/metamodelsattributecontentarticle/article.png';
        $this->typeClass = ContentArticle::class;
    }
}
