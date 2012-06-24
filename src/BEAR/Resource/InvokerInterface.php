<?php
/**
 * BEAR.Resource
 *
 * @package BEAR.Resource
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

/**
 * Resource request invoke interface
 *
 * @package BEAR.Resource
 * @author  Akihito Koriyama <akihito.koriyama@gmail.com>
 *
 * @ImplementedBy("BEAR\Resource\Invoker")
 */
interface InvokerInterface
{
    /**
     * Invoke resource request
     *
     * @param  Request $request
     * @return mixed
     */
    public function invoke(Request $request);

    /**
     * Invoke traversal
     *
     * invoke callable
     *
     * @param \Traversable $requests
     */
    public function invokeTraversal(\Traversable $requests);

    /**
     * Invoke Sync
     *
     * @param \SplObjectStorage $requests
     *
     * @return mixed
     */
    public function invokeSync(\SplObjectStorage $requests);

}
