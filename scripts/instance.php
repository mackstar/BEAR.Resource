<?php

namespace BEAR\Resource;

use Aura\Signal\HandlerFactory;
use Aura\Signal\Manager;
use Aura\Signal\ResultCollection;
use Aura\Signal\ResultFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Guzzle\Parser\UriTemplate\UriTemplate;
use Ray\Di\Definition;
use Ray\Di\Injector;

$invoker = new Invoker(
    new Linker(new AnnotationReader, new ArrayCache, new UriTemplate),
    new NamedParams(
        new SignalParam(
            new Manager(new HandlerFactory, new ResultFactory, new ResultCollection),
            new Param
        )
    ),
    new Logger
);

$resource = new Resource(
    new Factory(new SchemeCollection),
    $invoker,
    new Request($invoker),
    new Anchor(new UriTemplate, new AnnotationReader, new Request($invoker))
);

return $resource;
