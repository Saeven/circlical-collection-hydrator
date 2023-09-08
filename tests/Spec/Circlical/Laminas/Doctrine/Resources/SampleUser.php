<?php

namespace Spec\Circlical\Laminas\Doctrine\Resources;

class SampleUser
{
    private int $id;

    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
