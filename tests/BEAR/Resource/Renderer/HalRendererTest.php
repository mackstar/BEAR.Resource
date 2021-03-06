<?php

namespace BEAR\Resource\Renderer;

use Aura\Signal\HandlerFactory;
use Aura\Signal\Manager;
use Aura\Signal\ResultCollection;
use Aura\Signal\ResultFactory;
use BEAR\Resource\Invoker;
use BEAR\Resource\Linker;
use BEAR\Resource\Logger;
use BEAR\Resource\NamedParams;
use BEAR\Resource\Param;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\SignalParam;
use Doctrine\Common\Annotations\AnnotationReader;

class MockResource extends ResourceObject
{
    public $headers = ['head1' => 1];
    public $body = [
        'greeting' => 'hello'
    ];

    public function onGet($a, $b)
    {
        $this['posts'] = [$a, $b];

        return $this;
    }
}

class HalRendererTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var HalRenderer
     */
    private $halRenderer;

    /**
     * @var ResourceObject
     */
    private $resource;

    protected function setUp()
    {
        $this->halRenderer = new HalRenderer;
        $this->resource = new MockResource;
        $this->resource->uri = 'dummy://self/index';

    }

    public function testNew()
    {
        $this->assertInstanceOf('\BEAR\Resource\Renderer\HalRenderer', $this->halRenderer);
    }

    public function testRender()
    {
        $this->resource->setRenderer($this->halRenderer);
        $this->halRenderer->render($this->resource);
        $this->assertSame("application/hal+json; charset=UTF-8", $this->resource->headers['content-type']);

        return $this->resource;
    }

    /**
     * @depends testRender
     */
    public function testRenderView(ResourceObject $resource)
    {
        $this->assertContains('"greeting": "hello"', $resource->view);
    }

    public function testRenderBodyIsScalar()
    {
        $this->resource->body = 'hello';
        $this->resource->setRenderer($this->halRenderer);
        $this->halRenderer->render($this->resource);
        $this->assertContains('"value": "hello"', $this->resource->view);
    }

    public function testRenderHasLink()
    {
        $this->resource->links = ['rel1' => ['href' => 'page://self/rel1']];
        $this->resource->setRenderer($this->halRenderer);
        $this->halRenderer->render($this->resource);
        $links = '"_links": {
        "self": {
            "href": "dummy://self/index"
        },
        "rel1": {
            "href": "page://self/rel1"
        }
    }';
        $this->assertContains($links, $this->resource->view);
    }

    /**
     * @expectedException \BEAR\Resource\Exception\HrefNotFound
     */
    public function testRenderInvalidLink()
    {
        $this->resource->links = ['rel1' => 'page://self/rel1'];
        $this->resource->setRenderer($this->halRenderer);
        $this->halRenderer->render($this->resource);

    }

    public function testBodyHasRequest()
    {
        $invoker = new Invoker(
            new Linker(new AnnotationReader),
            new NamedParams(
                new SignalParam(
                    new Manager(new HandlerFactory, new ResultFactory, new ResultCollection),
                    new Param
                )
            ),
            new Logger
        );
        $request = new Request($invoker);
        $request->set(new MockResource, 'nop://mock', 'get', ['a'=>1, 'b'=>2]);
        $this->resource->body['req'] = $request;
        $this->resource->setRenderer($this->halRenderer);
        $this->halRenderer->render($this->resource);
        $this->assertContains('"greeting": "hello"', $this->resource->view);
    }
}
