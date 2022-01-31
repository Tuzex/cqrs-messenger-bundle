<?php

declare(strict_types=1);

namespace Tuzex\Bundle\Cqrs\Messenger\Test\DepedencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tuzex\Bundle\Cqrs\Messenger\DependencyInjection\CqrsMessengerExtension;
use Tuzex\Cqrs\CommandHandler;
use Tuzex\Cqrs\Messenger\MessengerCommandBus;
use Tuzex\Cqrs\Messenger\MessengerQueryBus;
use Tuzex\Cqrs\QueryHandler;

final class CqrsExtensionTest extends TestCase
{
    private CqrsMessengerExtension $cqrsMessengerExtension;
    private ContainerBuilder $containerBuilder;

    protected function setUp(): void
    {
        $this->cqrsMessengerExtension = new CqrsMessengerExtension();
        $this->containerBuilder = new ContainerBuilder();

        parent::setUp();
    }

    public function testItContainsMessengerConfigs(): void
    {
        $this->cqrsMessengerExtension->prepend($this->containerBuilder);

        $messengerConfigs = $this->resolveMessengerConfig();

        $this->assertArrayHasKey('default_bus', $messengerConfigs);
        $this->assertArrayHasKey('buses', $messengerConfigs);
    }

    /**
     * @dataProvider provideBusIds
     */
    public function testItRegistersExpectedBuses(string $busId): void
    {
        $this->cqrsMessengerExtension->prepend($this->containerBuilder);

        $this->assertArrayHasKey($busId, $this->resolveMessengerConfig()['buses']);
    }

    public function provideBusIds(): array
    {
        return [
            'command-bus' => [
                'busId' => 'tuzex.cqrs.command_bus',
            ],
            'query-bus' => [
                'busId' => 'tuzex.cqrs.query_bus',
            ],
        ];
    }

    /**
     * @dataProvider provideHandlerSettings
     */
    public function testItRegistersAutoconfigurationOfHandlers(string $id): void
    {
        $this->cqrsMessengerExtension->prepend($this->containerBuilder);

        $this->assertArrayHasKey($id, $this->containerBuilder->getAutoconfiguredInstanceof());
    }

    /**
     * @dataProvider provideHandlerSettings
     */
    public function testItSetsAutoconfigurationTags(string $id, array $tags): void
    {
        $this->cqrsMessengerExtension->prepend($this->containerBuilder);

        $autoconfiguration = $this->containerBuilder->getAutoconfiguredInstanceof()[$id];

        foreach ($tags as $tag => $configs) {
            $this->assertArrayHasKey($tag, $autoconfiguration->getTags());
            $this->assertContainsEquals($configs, $autoconfiguration->getTags()[$tag]);
        }
    }

    public function provideHandlerSettings(): array
    {
        return [
            'command-handler' => [
                'id' => CommandHandler::class,
                'tags' => [
                    'tuzex.cqrs.command_handler' => [],
                    'messenger.message_handler' => [
                        'bus' => 'tuzex.cqrs.command_bus',
                    ],
                ],
            ],
            'query-handler' => [
                'id' => QueryHandler::class,
                'tags' => [
                    'tuzex.cqrs.query_handler' => [],
                    'messenger.message_handler' => [
                        'bus' => 'tuzex.cqrs.query_bus',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideServiceIds
     */
    public function testItRegistersExpectedServices(string $serviceId): void
    {
        $this->cqrsMessengerExtension->load([], $this->containerBuilder);

        $this->assertTrue($this->containerBuilder->hasDefinition($serviceId));
    }

    public function provideServiceIds(): array
    {
        return [
            'command-bus' => [
                'serviceId' => MessengerCommandBus::class,
            ],
            'query-bus' => [
                'serviceId' => MessengerQueryBus::class,
            ],
        ];
    }

    private function resolveMessengerConfig(): array
    {
        return $this->resolveFrameworkConfig()['messenger'];
    }

    private function resolveFrameworkConfig(): array
    {
        return $this->containerBuilder->getExtensionConfig('framework')[0];
    }
}
