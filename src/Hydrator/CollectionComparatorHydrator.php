<?php

declare(strict_types=1);

namespace Circlical\Laminas\Doctrine\Hydrator;

use Closure;
use Doctrine\Inflector\Inflector;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\Persistence\ObjectManager;

use function call_user_func;

class CollectionComparatorHydrator extends DoctrineObject
{
    /** @var callable */
    private $instantiationClosure;

    public function __construct(ObjectManager $objectManager, Closure $instantiationClosure, bool $byValue = true, ?Inflector $inflector = null)
    {
        $this->instantiationClosure = $instantiationClosure;
        parent::__construct($objectManager, $byValue, $inflector);
    }

    public function hydrate(array $data, object $object): object
    {
        return call_user_func($this->instantiationClosure, $data, $object);
    }
}
