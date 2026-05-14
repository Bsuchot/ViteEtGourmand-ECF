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
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));

            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            }
        }
    }

    public function toArray(): array
    {
        $array      = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);

            // Sérialise les objets enfants imbriqués (ex: $plats = [Plat, ...])
            if (is_array($value)) {
                $value = array_map(
                    fn($item) => $item instanceof self ? $item->toArray() : $item,
                    $value
                );
            } elseif ($value instanceof self) {
                $value = $value->toArray();
            }

            $array[$property->getName()] = $value;
        }

        return $array;
    }
}