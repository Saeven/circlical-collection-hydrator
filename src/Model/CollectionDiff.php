<?php

declare(strict_types=1);

namespace Circlical\Laminas\Doctrine\Model;

use Doctrine\Common\Collections\ArrayCollection;

use function array_diff;
use function array_key_exists;
use function array_keys;

/**
 * Take two collections of objects with a getId() method, and figure which
 * should be added, and which should be removed.
 *
 * You can call this method as a convenience to reduce code mass in the system.  For example:
 *
 * $diff = new CollectionDiff(
 *     $group->getAdmins(),
 *     $this->userMapper->getRepository()->findBy(['id' => $idList]),
 *     true // project data onto the retained entities
 * );
 *
 * $diff->processRemovals(function($user){
 *     $group->removeAdmin($user);
 * })->processAdditions(function($user){
 *     $group->addAdmin($user);
 * });
 */
class CollectionDiff
{
    private ArrayCollection $shouldBeAdded;
    private ArrayCollection $shouldBeRemoved;

    public function __construct(array $old, array $new, bool $projectValues = false)
    {
        $this->shouldBeAdded = new ArrayCollection();
        $this->shouldBeRemoved = new ArrayCollection();

        $oldArray = [];
        $newArray = [];

        foreach ($old as $object) {
            if ($object instanceof CollectionDiffInterface) {
                $oldArray[$object->getDiffIdentifier()] = $object;
                continue;
            }

            if (!method_exists($object, 'getId')) {
                throw new \RuntimeException("The comparator needs for your object to implement CollectionDiffInterface, or have a getId method.");
            }

            $oldArray[$object->getId()] = $object;
        }

        foreach ($new as $object) {
            if ($object instanceof CollectionDiffInterface) {
                $newArray[$object->getDiffIdentifier()] = $object;
                continue;
            }

            if (!method_exists($object, 'getId')) {
                throw new \RuntimeException("The comparator needs for your object to implement CollectionDiffInterface, or have a getId method.");
            }

            $newArray[$object->getId()] = $object;
        }

        if ($projectValues) {
            foreach ($oldArray as $oldId => $oldObject) {
                if (array_key_exists($oldId, $newArray)) {
                    $oldObject->copyValuesFrom($newArray[$oldId]);
                }
            }
        }

        $oldIds = array_keys($oldArray);
        $newIds = array_keys($newArray);

        foreach (array_diff($oldIds, $newIds) as $id) {
            $this->shouldBeRemoved->add($oldArray[$id]);
        }

        foreach (array_diff($newIds, $oldIds) as $id) {
            $this->shouldBeAdded->add($newArray[$id]);
        }
    }

    public function getShouldBeAdded(): ArrayCollection
    {
        return $this->shouldBeAdded;
    }

    public function getShouldBeRemoved(): ArrayCollection
    {
        return $this->shouldBeRemoved;
    }

    public function processRemovals(callable $adjustmentFunction): CollectionDiff
    {
        foreach ($this->shouldBeRemoved as $item) {
            $adjustmentFunction($item);
        }

        return $this;
    }

    public function processAdditions(callable $adjustmentFunction): CollectionDiff
    {
        foreach ($this->shouldBeAdded as $item) {
            $adjustmentFunction($item);
        }

        return $this;
    }

    public function containsChanges(): bool
    {
        return $this->shouldBeAdded->count() || $this->shouldBeRemoved->count();
    }
}
