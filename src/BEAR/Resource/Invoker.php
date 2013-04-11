<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @package BEAR.Resource
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

use BEAR\Resource\AbstractObject as ResourceObject;
use BEAR\Resource\Exception\MethodNotAllowed;
use Ray\Aop\Weave;
use Ray\Di\Di\Scope;
use Ray\Di\Definition;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

/**
 * Resource request invoker
 *
 * @package BEAR.Resource
 *
 * @Scope("singleton")
 */
class Invoker implements InvokerInterface
{
    /**
     * @var \BEAR\Resource\Linker
     */
    private $linker;

    /**
     * Logger
     *
     * @var \BEAR\Resource\Logger
     */
    private $logger;

    /**
     * @var ReflectiveParams
     */
    protected $params;

    /**
     * Method OPTIONS
     *
     * @var string
     */
    const OPTIONS = 'options';

    /**
     * Method HEAD
     *
     * @var string
     */
    const HEAD = 'head';

    /**
     * ProviderInterface annotation
     *
     * @var string
     */
    const ANNOTATION_PROVIDES = 'Provides';


    /**
     * Return signal manager
     *
     * @return \Aura\Signal\Manager
     */
    public function getSignal()
    {
        return $this->params->getSignal();
    }

    /**
     * @param LinkerInterface  $linker
     * @param ReflectiveParams $params
     *
     * @Inject
     */
    public function __construct(
        LinkerInterface $linker,
        ReflectiveParams $params
    ) {
        $this->linker = $linker;
        $this->params = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function setResourceClient(ResourceInterface $resource)
    {
        $this->linker->setResource($resource);
    }

    /**
     * Resource logger setter
     *
     * @param LoggerInterface $logger
     *
     * @Inject(optional=true)
     */
    public function setResourceLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function invoke(Request $request)
    {
        $method = 'on' . ucfirst($request->method);
        $isWeave = $request->ro instanceof Weave;
        /** @var $request->ro \Ray\Aop\Weave */
        /** @noinspection PhpUndefinedMethodInspection */
        $ro = $isWeave ? $request->ro->___getObject() : $request->ro;
        if ($isWeave && $request->method !== Invoker::OPTIONS && $request->method !== Invoker::HEAD) {
            $weave = $request->ro;
            /** @noinspection PhpUnusedLocalVariableInspection */
            /** @var $weave Callable */
            $result = $weave([$this->params, 'getParams'], $method, $request->query);
            goto completed;
        }
        if (method_exists($ro, $method) !== true) {
            return $this->methodNotExists($ro, $request, $method);
        }
        $params = $this->params->getParams($ro, $method, $request->query);
        try {
            $result = call_user_func_array([$ro, $method], $params);
        } catch (\Exception $e) {
            // @todo implements "Exception signal"
            throw $e;
        }
        // link
        completed:
        if ($request->links) {
            $result = $this->linker->invoke($ro, $request, $result);
        }
        if (!$result instanceof AbstractObject) {
            $ro->body = $result;
            $result = $ro;
            if ($result instanceof Weave) {
                $result = $result->___getObject();
            }

        }
        // request / result log
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($request, $result);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function invokeTraversal(\Traversable $requests)
    {
        foreach ($requests as &$element) {
            if ($element instanceof Request || is_callable($element)) {
                $element = $element();
            }
        }

        return $requests;
    }

    /**
     * {@inheritDoc}
     */
    public function invokeSync(\SplObjectStorage $requests)
    {
        $requests->rewind();
        $data = new \ArrayObject();
        while ($requests->valid()) {
            // each sync request method call.
            $request = $requests->current();
            if (method_exists($request->ro, 'onSync')) {
                call_user_func([$request->ro, 'onSync'], $request, $data);
            }
            $requests->next();
        }
        // onFinalSync summarize all sync request data.
        /** @noinspection PhpUndefinedVariableInspection */
        $result = call_user_func([$request->ro, 'onFinalSync'], $request, $data);

        return $result;
    }

    /**
     * Return available resource request method
     *
     * @param ResourceObject $ro
     *
     * @return array
     */
    protected function getOptions(ResourceObject $ro)
    {
        $ref = new \ReflectionClass($ro);
        $methods = $ref->getMethods();
        $allow = [];
        foreach ($methods as $method) {
            $isRequestMethod = (substr($method->name, 0, 2) === 'on') && (substr($method->name, 0, 6) !== 'onLink');
            if ($isRequestMethod) {
                $allow[] = strtolower(substr($method->name, 2));
            }
        }
        $params = [];
        foreach ($allow as $method) {
            $refMethod = new \ReflectionMethod($ro, 'on' . $method);
            $parameters = $refMethod->getParameters();
            $paramArray = [];
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                $param = $parameter->isOptional() ? "({$name})" : $name;
                $paramArray[] = $param;
            }
            $key = "param-{$method}";
            $params[$key] = implode(',', $paramArray);
        }
        $result = ['allow' => $allow, 'params' => $params];

        return $result;
    }

    /**
     * @param AbstractObject $ro
     * @param Request        $request
     * @param                $method
     *
     * @return AbstractObject
     * @throws Exception\MethodNotAllowed
     */
    private function methodNotExists(AbstractObject $ro, Request $request, $method)
    {
        if ($request->method === self::OPTIONS) {
            return $this->onOptions($ro);
        }
        if ($method === 'onHead' && method_exists($ro, 'onGet')) {
            return $this->onHead($request);
        } else {
            throw new Exception\MethodNotAllowed(get_class($request->ro) . "::$method()", 405);
        }
    }

    /**
     * @param AbstractObject $ro resource object
     *
     * @return AbstractObject
     */
    private function onOptions(AbstractObject $ro)
    {
        $options = $this->getOptions($ro);
        $ro->headers['allow'] = $options['allow'];
        $ro->headers += $options['params'];
        $ro->body = null;

        return $ro;
    }

    /**
     * @param Request $request
     *
     * @return AbstractObject
     */
    private function onHead(Request $request)
    {
        $ro = ($request->ro instanceof Weave) ? $request->ro->___getObject() :  $request->ro;
        if (method_exists($ro, 'onGet')) {
            $params = $this->params->getParams($ro, 'onGet', $request->query);
            call_user_func_array([$ro, 'onGet'], $params);
        }
        $ro->body = '';

        return $ro;
    }
}
