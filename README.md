# Collection Hydrator

This is a collection hydrator that builds on available hydrators, that prevents cases when Doctrine issues duplicate key updates when Laminas Forms and Collections are involved.  This issue can be evidenced [here](https://github.com/Saeven/CollectionHydration)


## Usage

Installation is very simple, after installing the package with composer, in your form Factory, you will specify the hydrator strategy for your collection to be "CollectionDiffStrategy".

```php

    <?php
    
    declare(strict_types=1);
    
    namespace HydrationTest\Factory\Form;
    
    use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
    use Doctrine\ORM\EntityManager;
    use HydrationTest\Form\Hydrator\CollectionDiffStrategy;
    use HydrationTest\Form\IngredientAmountFieldset;
    use HydrationTest\Form\RecipeForm;
    use Laminas\Form\FormElementManager;
    use Laminas\ServiceManager\Factory\FactoryInterface;
    use Psr\Container\ContainerInterface;
    
    class RecipeFormFactory implements FactoryInterface
    {
        public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
        {
            $hydrator = new DoctrineHydrator($container->get(EntityManager::class), false);
            $hydrator->addStrategy('ingredient_amounts', new CollectionDiffStrategy(true));
    
            return (new RecipeForm(
                $container->get(FormElementManager::class)->get(IngredientAmountFieldset::class, $options ?? []),
                $options ?? []
            ))
                ->setHydrator($hydrator)
                ->setObject($options['recipe']);
        }
    }
```

Then, in your fieldset factory, you will apply the CollectionComparatorHydrator to return clean objects of the new type.

```php
    <?php
    
    declare(strict_types=1);
    
    namespace HydrationTest\Factory\Form;
    
    use Doctrine\ORM\EntityManager;
    use HydrationTest\Entity\Ingredient;
    use HydrationTest\Entity\IngredientAmount;
    use HydrationTest\Form\Hydrator\CollectionComparatorHydrator;
    use HydrationTest\Form\IngredientAmountFieldset;
    use Laminas\ServiceManager\Factory\FactoryInterface;
    use Psr\Container\ContainerInterface;
    
    class IngredientAmountFieldsetFactory implements FactoryInterface
    {
        public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
        {
            $recipe = $options['recipe'];
    
            /** @var  EntityManager $entityManager */
            $entityManager = $container->get(EntityManager::class);
            $collectionHydrator = new CollectionComparatorHydrator($entityManager, function (array $data, object $object) use ($entityManager, $recipe) {
                $ingredient = $entityManager->getRepository(Ingredient::class)->findOneBy(['id' => $data['ingredient']]);
                return new IngredientAmount($recipe, $ingredient, $data['tablespoons'] ?? 0);
            }, false);
    
    
            return (new IngredientAmountFieldset($entityManager, 'ingredient_amounts'))
                ->setHydrator($collectionHydrator)
                ->setObject(new IngredientAmount($options['recipe'], null, null));
        }
    }

```

As last step, we must implement the necessary comparators on the objects that are hydrated by the fieldset; see CollectionDiffInterface.

```php
    <?php
    
    declare(strict_types=1);
    
    namespace HydrationTest\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    use HydrationTest\Model\CollectionDiffInterface;
    
    /**
     * @ORM\Entity
     * @ORM\Table(name="recipes_ingredients");
     */
    class IngredientAmount implements CollectionDiffInterface
    {
        /**
         * @ORM\Id
         * @ORM\ManyToOne(targetEntity="Recipe", inversedBy="ingredient_amounts")
         * @ORM\JoinColumn(name="recipe_id", referencedColumnName="id", onDelete="cascade")
         *
         * @var Recipe
         */
        private $recipe;
    
        /**
         * @ORM\Id
         * @ORM\ManyToOne(targetEntity="Ingredient")
         * @ORM\JoinColumn(name="ingredient_id", referencedColumnName="id", onDelete="cascade")
         *
         * @var ?Ingredient
         */
        private $ingredient;
    
        /**
         * @ORM\Column(type="integer", nullable=false, options={"default":0, "unsigned":true})
         *
         * @var ?int
         */
        private $tablespoons;
    
        public function __construct(Recipe $recipe, ?Ingredient $ingredient, ?int $tablespoons)
        {
            $this->recipe = $recipe;
            $this->ingredient = $ingredient;
            $this->tablespoons = $tablespoons;
        }
    
        public function getIngredient(): Ingredient
        {
            return $this->ingredient;
        }
    
        public function setTablespoons(int $tablespoons): void
        {
            $this->tablespoons = $tablespoons;
        }
    
        public function getTablespoons(): int
        {
            return $this->tablespoons;
        }
    
        public function getDiffIdentifier(): string
        {
            return $this->recipe->getId() . '-' . $this->ingredient->getId();
        }
    
        public function copyValuesFrom(object $object): void
        {
            if (!$object instanceof IngredientAmount) {
                return;
            }
    
            $this->tablespoons = $object->getTablespoons();
        }
    }
```

After these small changes, your collections will no longer issue update statements that cause duplicate keys.
