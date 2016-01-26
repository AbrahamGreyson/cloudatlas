<?php
/**
 * @link  : http://www.yinhexi.com/
 * @author: AbrahamGreyson <82011220@qq.com>
 * @date  : 01/02/2016
 */

namespace CloudStorage\Exceptions;


use CloudStorage\Contracts\CloudStorageExceptionInterface;
use RuntimeException;

class CloudStorageException extends RuntimeException implements
    CloudStorageExceptionInterface
{
    
}