<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

/**
 * Interface for resource log writer
 */
interface LogWriterInterface
{
    /**
     * Resource log write
     *
     * @param RequestInterface $request
     * @param ResourceObject   $result
     *
     * @return bool true if log written
     */
    public function write(RequestInterface $request, ResourceObject $result);
}
