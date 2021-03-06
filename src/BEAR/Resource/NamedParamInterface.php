<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

use Ray\Aop\MethodInvocation;

/**
 * Resource request invoke interface
 *
 * @ImplementedBy("BEAR\Resource\Invoker")
 */
interface NamedParamInterface
{
    /**
     * Return parameters
     *
     * @param MethodInvocation $invocation
     *
     * @return mixed
     */
    public function invoke(MethodInvocation $invocation);

    /**
     * Attach parameter provider
     *
     * @param string                 $varName
     * @param ParamProviderInterface $provider
     *
     * @return self
     */
    public function attachParamProvider($varName, ParamProviderInterface $provider);
}
