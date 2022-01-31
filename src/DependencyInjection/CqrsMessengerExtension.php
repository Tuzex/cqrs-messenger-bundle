<?php

declare(strict_types=1);

namespace Tuzex\Bundle\Cqrs\Messenger\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Tuzex\Cqrs\CommandHandler;
use Tuzex\Cqrs\QueryHandler;

final class CqrsMessengerExtension extends Extension implements ExtensionInterface, PrependExtensionInterface
{
    private FileLocator $fileLocator;

    public function __construct()
    {
        $this->fileLocator = new FileLocator(__DIR__.'/../Resources/config');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configuration = new Configuration(false);
        $configs = $this->processConfiguration($configuration, $container->getExtensionConfig('framework'));

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'default_bus' => $configs['messenger']['default_bus'] ?? 'tuzex.cqrs.command_bus',
                'buses' => [
                    'tuzex.cqrs.command_bus' => [],
                    'tuzex.cqrs.query_bus' => [],
                ],
            ],
        ]);

        $container->registerForAutoconfiguration(CommandHandler::class)
            ->addTag('tuzex.cqrs.command_handler')
            ->addTag('messenger.message_handler', [
                'bus' => 'tuzex.cqrs.command_bus',
            ]);

        $container->registerForAutoconfiguration(QueryHandler::class)
            ->addTag('tuzex.cqrs.query_handler')
            ->addTag('messenger.message_handler', [
                'bus' => 'tuzex.cqrs.query_bus',
            ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, $this->fileLocator);
        $loader->load('services.xml');
    }
}
