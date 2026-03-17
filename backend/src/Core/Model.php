<?php

namespace App\Core;

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

}