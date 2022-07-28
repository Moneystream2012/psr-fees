<?php

namespace App\Tests;

use App\Command\FeesCommand;
use App\Infra\Client\ApiClient;
use App\Tests\Mock\Client\ApiClientMock;
use GuzzleHttp\Exception\GuzzleException;
use Mockery;
use PHPUnit\Framework\TestCase;

class FeesTest extends TestCase
{
    private ApiClient $apiClient;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $mock = Mockery::mock(
            'overload:' . ApiClient::class,
            ['getResourceByUri' => ApiClientMock::getResourceByUri(),]
        );

        $mock->shouldReceive('getResourceByPath')
            ->withAnyArgs()
            ->andReturn('getResourceByPath')
            ->andReturnUsing(fn ($basePath, $bin) => ApiClientMock::getResourceByPath($bin));

        $this->apiClient = new ApiClient();
    }

    /**
     * @throws \ReflectionException|GuzzleException
     */
    public function testDefaultRate_USD_BasedOnEUR(): void
    {
        $currencies = $this->apiClient::getResourceByUri('currencies-fake-uri', ['apikey' => 'fake-key']);

        $reflectionFeesCommand = new \ReflectionClass('\App\Command\FeesCommand');
        $getCurrencyRate = $reflectionFeesCommand->getMethod('currencyRate');
        $getCurrencyRate->setAccessible(true);

        $feesCommand = new FeesCommand();
        $rate = $getCurrencyRate->invokeArgs($feesCommand, [$currencies, 'USD']);

        $this->assertTrue($rate === 1.01425, 'Default rate USD based on EUR');
    }

    public function absentIsEu($bin, $expected)
    {
        $reflectionFeesCommand = new \ReflectionClass('\App\Command\FeesCommand');
        $isEu = $reflectionFeesCommand->getMethod('isEu');
        $isEu->setAccessible(true);

        $feesCommand = new FeesCommand();

        $this->expectException(\InvalidArgumentException::class);
        $isEu->invokeArgs($feesCommand, ['fake-bin-api', $bin]);
    }

    /**
     * @dataProvider isEuProvider
     */
    public function testIsEu($bin, $expected)
    {
        $reflectionFeesCommand = new \ReflectionClass('\App\Command\FeesCommand');
        $isEu = $reflectionFeesCommand->getMethod('isEu');
        $isEu->setAccessible(true);

        $feesCommand = new FeesCommand();
        $actual = $isEu->invokeArgs($feesCommand, ['fake-bin-api', $bin]);

        $this->assertSame($expected, $actual);
    }

    public function isEuProvider(): array
    {
        return [
            ['45717360', true],
            ['516793', true],
            ['41417360', false],    // US
        ];
    }
}
