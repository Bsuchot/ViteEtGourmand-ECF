<?php

namespace App\Core;

use ReflectionClass;

class Model
{

    public static function createAndHydrate(array $data): static
    {
        $model = new static();
        $model->hydrate($data);

        return $model;
    }

    public function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $methodName = "set".str_replace(' ', '', ucwords(str_replace(array('_', '-'), ' ', $key)));

            if (method_exists($this, $methodName)) {
                $this->{$methodName}($value);
            }
        }
    }
    public function toArray(): array
    {
        $array = [];

        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($this);
        }
        return $array;
    }

}