<?php

declare(strict_types=1);

namespace Circlical\Laminas\Doctrine\Hydrator;

use Circlical\Laminas\Doctrine\Model\CollectionDiff;
use Circlical\Laminas\Doctrine\Model\CollectionDiffInterface;
use Doctrine\Inflector\Inflector;
use Doctrine\Laminas\Hydrator\Strategy\AbstractCollectionStrategy;

class CollectionDiffStrategy extends AbstractCollectionStrategy
{
    private bool $projectValues;

    public function __construct(bool $projectValues, ?Inflector $inflector = null)
    {
        parent::__construct($inflector);
        $this->projectValues = $projectValues;
    }

    /**
     * @inheritDoc
     */
    public function hydrate($value, ?array $data)
    {
        $collection = $this->getCollectionFromObjectByReference();
        $collectionArray = $collection->toArray();

        (new CollectionDiff($collectionArray, $value, $this->projectValues))
            ->processAdditions(function (CollectionDiffInterface $object) use ($collection) {
                $collection->add($object);
            })
            ->processRemovals(function (CollectionDiffInterface $object) use ($collection) {
                $collection->removeElement($object);
            });

        return $collection;
    }
}
