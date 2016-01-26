<?php
namespace CloudStorage\Exceptions;


use CloudStorage\Contracts\CloudStorageExceptionInterface;
use RuntimeException;

class CloudStorageException extends RuntimeException implements
    CloudStorageExceptionInterface
{
    
}