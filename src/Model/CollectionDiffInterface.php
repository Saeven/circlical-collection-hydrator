<?php

declare(strict_types=1);

namespace Circlical\Laminas\Doctrine\Model;

interface CollectionDiffInterface
{
    public function getDiffIdentifier(): string;

    public function copyValuesFrom(object $object): void;
}
