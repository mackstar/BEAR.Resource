<?php

namespace BEAR\Resource;

use Ray\Aop\MethodInvocation;
use ReflectionParameter;

/**
 * This file is part of the {package} package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
interface ParamInterface
{
    /**
     * Set method invocation and parameter reflection
     *
     * @param MethodInvocation    $invocation
     * @param ReflectionParameter $parameter
     *
     * @return mixed
     */
    public function set(MethodInvocation $invocation, ReflectionParameter $parameter);

    /**
     * Return method invocation
     *
     * @return MethodInvocation
     */
    public function getMethodInvocation();

    /**
     * Return parameter
     *
     * @return ReflectionParameter
     */
    public function getParameter();

    /**
     * Inject argument
     *
     * @param $arg
     *
     * @return string 'Aura\Signal\Manager::STOP'
     */
    public function inject($arg);
}
