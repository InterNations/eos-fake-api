<?php
namespace InterNations\Component\HttpMock\PHPUnit;

trait HttpMock
{
    public static function getHttpMockDefaultPort(): int
    {
        return 28080;
    }

    public static function getHttpMockDefaultHost(): string
    {
        return 'localhost';
    }

    /** @var HttpMockFacade|HttpMockFacadeMap */
    protected static $staticHttp;

    /** @var HttpMockFacade|HttpMockFacadeMap */
    protected $http;

    protected static function setUpHttpMockBeforeClass(
        ?int $port = null,
        ?string $host = null,
        ?string $basePath = null,
        ?string $name = null
    ): void
    {
        $port = $port ?: static::getHttpMockDefaultPort();
        $host = $host ?: static::getHttpMockDefaultHost();

        $facade = new HttpMockFacade($port, $host, $basePath);

        if ($name === null) {
            static::$staticHttp = $facade;
        } elseif (static::$staticHttp instanceof HttpMockFacadeMap) {
            static::$staticHttp = new HttpMockFacadeMap([$name => $facade] + static::$staticHttp->all());
        } else {
            static::$staticHttp = new HttpMockFacadeMap([$name => $facade]);
        }

        ServerManager::getInstance()->add($facade->server);
    }

    protected function setUpHttpMock(): void
    {
        static::assertHttpMockSetup();

        $this->http = clone static::$staticHttp;
    }

    protected static function assertHttpMockSetup(): void
    {
        if (static::$staticHttp) {
            return;
        }

        static::fail(
            sprintf(
                'Static HTTP mock facade not present. Did you forget to invoke static::setUpHttpMockBeforeClass()'
                . ' in %s::setUpBeforeClass()?',
                static::class
            )
        );
    }

    protected function tearDownHttpMock(): void
    {
        if (!$this->http) {
            return;
        }

        $http = $this->http;
        $this->http = null;
        $http->each(
            function (HttpMockFacade $facade): void {
                $this->assertSame(
                    '',
                    (string) $facade->server->getIncrementalErrorOutput(),
                    'HTTP mock server standard error output should be empty'
                );
            }
        );
    }

    protected static function tearDownHttpMockAfterClass(): void
    {
        static::$staticHttp->each(
            static function (HttpMockFacade $facade): void {
                $facade->server->stop();
                ServerManager::getInstance()->remove($facade->server);
            }
        );
    }
}
