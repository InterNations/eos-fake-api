<?php
namespace InterNations\Component\HttpMock\Tests;

use Guzzle\Http\Client;
use Symfony\Component\HttpFoundation\Request;
use InterNations\Component\HttpMock\Expectation;
use InterNations\Component\HttpMock\Matcher\MatcherFactory;
use InterNations\Component\HttpMock\MockBuilder;
use InterNations\Component\HttpMock\Server;
use PHPUnit_Framework_TestCase as TestCase;
use DateTime;
use DateTimeZone;
use InterNations\Component\HttpMock\Tests\Fixtures\Request as TestRequest;

class MockBuilderIntegrationTest extends TestCase
{
    /** @var MockBuilder */
    private $builder;

    /** @var MatcherFactory */
    private $matches;

    /** @var Server */
    private $server;

    public function setUp()
    {
        $this->matches = new MatcherFactory();
        $this->builder = new MockBuilder($this->matches);
        $this->server = new Server(HTTP_MOCK_PORT, HTTP_MOCK_HOST);
        $this->server->start();
        $this->server->clean();
    }

    public function tearDown()
    {
        $this->server->stop();
    }

    public function testCreateExpectation()
    {
        $builder = $this->builder
            ->when()
                ->pathIs('/foo')
                ->methodIs($this->matches->regex('/POST/'))
                ->callback(static function ($request) {
                    error_log('CLOSURE MATCHER: ' . $request->getMethod() . ' ' . $request->getPathInfo());
                    return true;
                })
            ->then()
                ->statusCode(401)
                ->body('response body')
                ->header('X-Foo', 'Bar')
            ->end();

        $this->assertSame($this->builder, $builder);

        $expectations = $this->builder->getExpectations();

        $this->assertCount(1, $expectations);
        /** @var Expectation $expectation */
        $expectation = current($expectations);

        $request = new TestRequest();
        $request->setMethod('POST');
        $request->setPathInfo('/foo');

        $run = 0;
        $oldValue = ini_set('error_log', '/dev/null');
        foreach ($expectation->getMatcherClosures() as $closure) {
            $this->assertTrue($closure($request));

            $unserializedClosure = unserialize(serialize($closure));
            $this->assertTrue($unserializedClosure($request));

            $run++;
        }
        ini_set('error_log', $oldValue);
        $this->assertSame(3, $run);

        $expectation->getResponse()->setDate(new DateTime('2012-11-10 09:08:07', new DateTimeZone('UTC')));
        $response = "HTTP/1.0 401 Unauthorized\r\nCache-Control: no-cache\r\nDate:          Sat, 10 Nov 2012 09:08:07 GMT\r\nX-Foo:         Bar\r\n\r\nresponse body";
        $this->assertSame($response, (string)$expectation->getResponse());


        $this->server->setUp($this->builder->getExpectations());

        $client = $this->server->getClient();

        $this->assertSame('response body', (string) $client->post('/foo')->send()->getBody());

        $this->assertContains('CLOSURE MATCHER: POST /foo', $this->server->getErrorOutput());
    }
}
