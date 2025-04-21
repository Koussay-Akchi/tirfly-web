<?php

namespace App\Serializer;

class CircularReferenceHandler
{
    public static function handle($object)
    {
        // Return a unique identifier for the object when a circular reference is encountered.
        return method_exists($object, 'getId') ? $object->getId() : spl_object_hash($object);
    }
}
