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
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_contentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeContentArticleBundle\DependencyInjection;

use InspiredMinds\ContaoFileUsage\ContaoFileUsageBundle;
use MetaModels\AttributeContentArticleBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

use function assert;
use function in_array;
use function is_array;
use function is_bool;

/**
 * This is the class that loads and manages the bundle configuration
 *
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class MetaModelsAttributeContentArticleExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('listeners.yml');
        $loader->load('services.yml');

        $configuration = $this->getConfiguration($configs, $container);
        assert($configuration instanceof Configuration);
        $config = $this->processConfiguration($configuration, $configs);

        $bundles = $container->getParameter('kernel.bundles');
        assert(is_array($bundles));

        // Load configuration for the file usage extension.
        if (in_array(ContaoFileUsageBundle::class, $bundles, true) && (bool) ($config['file_usage'] ?? false)) {
            $loader->load('file_usage/services.yml');
        }

        // Schema manager.
        $typeNames                = $container->hasParameter('metamodels.managed-schema-type-names')
            ? $container->getParameter('metamodels.managed-schema-type-names')
            : null;
        $managedSchemaTypeNames   = is_array($typeNames) ? $typeNames : [];
        $managedSchemaTypeNames[] = 'contentarticle';
        $container->setParameter('metamodels.managed-schema-type-names', $managedSchemaTypeNames);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration();
    }
}
