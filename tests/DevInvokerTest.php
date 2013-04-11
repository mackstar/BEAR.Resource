<?php

use BEAR\Resource\DevInvoker;

namespace BEAR\Resource;

//require __DIR__ . '/InvokerTest.php';

use Ray\Di\Definition;
use Ray\Di\Annotation;
use Ray\Di\Config;
use Ray\Di\Forge;
use Ray\Di\Container;
use Ray\Di\Manager;
use Ray\Di\Injector;
use Ray\Di\EmptyModule;
use Ray\Aop\Weaver;
use Ray\Aop\Bind;
use Ray\Aop\ReflectiveMethodInvocation;
use BEAR\Resource\Mock\User;
use Doctrine\Common\Annotations\AnnotationReader as Reader;

/**
 * Test class for BEAR.Resource.
 */
class DevInvokerTest extends \PHPUnit_Framework_TestCase
{
    protected $signal;
    protected $invoker;

    protected function setUp()
    {
        $signal = require dirname(__DIR__) . '/vendor/aura/signal/scripts/instance.php';
        $signal->handler(
            '\BEAR\Resource\ReflectiveParams',
            \BEAR\Resource\ReflectiveParams::SIGNAL_PARAM . 'Provides',
            new SignalHandler\Provides
        );
        $signal->handler(
            '\BEAR\Resource\ReflectiveParams',
            \BEAR\Resource\ReflectiveParams::SIGNAL_PARAM . 'login_id',
            function (
                $return,
                \ReflectionParameter $parameter,
                ReflectiveMethodInvocation $invocation,
                Definition $definition
            ) {
                $return->value = 1;

                return \Aura\Signal\Manager::STOP;
            }
        );
        $this->invoker = new DevInvoker(
            new Linker(new Reader),
            new ReflectiveParams(
                new Config(new Annotation(new Definition, new Reader)),
                $signal
            )
        );


        $resource = new \testworld\ResourceObject\User;
        $resource->uri = 'dummy://self/User';
        $this->request = new Request($this->invoker);
        $this->request->method = 'get';
        $this->request->ro = $resource;
        $this->request->query = ['id' => 1];
    }

    /**
     * @test
     */
    public function invoke()
    {
        $actual = $this->invoker->invoke($this->request)->body;
        $expected = ['id' => 2, 'name' => 'Aramis', 'age' => 16, 'blog_id' => 12];
        $ro = $this->request->ro;
        $this->assertSame($actual, $expected);

        return $ro->headers;
    }

    /**
     * @depends invoke
     */
    public function test_isHEADER_EXECUTION_TIME_headerExists(array $headers)
    {
        $this->assertArrayHasKey(DevInvoker::HEADER_EXECUTION_TIME, $headers);
    }

    /**
     * @depends invoke
     */
    public function test_HEADER_EXECUTION_TIME_isPlus(array $headers)
    {
        $this->assertTrue($headers[DevInvoker::HEADER_EXECUTION_TIME] > 0);
    }

    /**
     * @depends invoke
     */
    public function test_isHEADER_MEMORY_USAGEExists(array $headers)
    {
        $this->assertArrayHasKey(DevInvoker::HEADER_MEMORY_USAGE, $headers);
    }

    /**
     * @depends invoke
     */
    public function test_HEADER_MEMORY_USAGE_isPlus(array $headers)
    {
        $this->assertTrue($headers[DevInvoker::HEADER_MEMORY_USAGE] > 0);
    }

    /**
     * (non-PHPdoc)
     * @see BEAR\Resource.InvokerTest::test_invokeWeave()
     *
     * @test
     */
    public function invokeWeave()
    {
        $bind = new Bind;
        $bind->bindInterceptors('onGet', [new \testworld\Interceptor\Log]);
        $weave = new Weaver(new \testworld\ResourceObject\Weave\Book, $bind);
        $this->request->ro = $weave;
        $this->request->method = 'get';
        $this->request->query = ['id' => 1];
        $actual = $this->invoker->invoke($this->request);

        $ro = $this->request->ro->___getObject();
        $this->assertInstanceOf('testworld\ResourceObject\Weave\Book', $ro);

        return $ro->headers;
    }

    /**
     * @param array $headers
     *
     * @depends invokeWeave
     */
    public function testInvokeWeavedResourceHasLogInObjectHeader(array $headers)
    {
        $this->assertArrayHasKey(DevInvoker::HEADER_INTERCEPTORS, $headers);
    }

    /**
     * @param array $headers
     *
     * @depends invokeWeave
     */
    public function testInvokeWeavedResourceLogInObjectHeaderIsBind(array $headers)
    {
        $this->assertTrue(isset($headers[DevInvoker::HEADER_INTERCEPTORS]));
    }

    /**
     * @param array $headers
     *
     * @depends invokeWeave
     */
    public function testInvokeWeavedResourceLogInObjectHeaderContents(array $headers)
    {
        $bind = (array)$headers[DevInvoker::HEADER_INTERCEPTORS];
        $this->assertSame(
            json_encode(['onGet' => ['testworld\Interceptor\Log']]),
            $headers[DevInvoker::HEADER_INTERCEPTORS]
        );
    }
}
