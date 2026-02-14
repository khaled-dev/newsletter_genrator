<?php

namespace App\DTOs;

use Exception;
use ReflectionClass;

abstract class ArticleParent
{
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();

        $array = [];
        foreach ($properties as $property) {
            $array[$property->getName()] = $property->getValue($this);
        }

        return $array;
    }

    public function __call(string $method, array $args)
    {
        if (str_starts_with($method, 'get')) {
            $property = $this->camelToSnake(substr($method, 3));

            if (property_exists($this, $property)) {
                return $this->$property;
            }
        }

        throw new Exception("Method $method does not exist.");
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
