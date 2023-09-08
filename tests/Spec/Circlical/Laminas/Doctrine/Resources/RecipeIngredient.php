<?php

namespace Spec\Circlical\Laminas\Doctrine\Resources;

use Circlical\Laminas\Doctrine\Model\CollectionDiffInterface;

class RecipeIngredient implements CollectionDiffInterface
{
    private int $recipeId;

    private int $ingredientId;

    private int $tablespoons;

    public function __construct()
    {
        // pretend this is hydrated by reference
    }

    public function getDiffIdentifier(): string
    {
        return $this->recipeId . '-' . $this->ingredientId;
    }

    public function copyValuesFrom(object $object): void
    {
        if (!$object instanceof self) {
            return;
        }

        $this->tablespoons = $object->getTablespoons();
    }

    public function getTablespoons(): int
    {
        return $this->tablespoons;
    }
}
