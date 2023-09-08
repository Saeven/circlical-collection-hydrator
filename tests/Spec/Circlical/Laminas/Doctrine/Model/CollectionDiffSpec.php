<?php

namespace Spec\Circlical\Laminas\Doctrine\Model;

use PhpSpec\ObjectBehavior;
use Spec\Circlical\Laminas\Doctrine\Resources\Foo;
use Spec\Circlical\Laminas\Doctrine\Resources\RecipeIngredient;
use Spec\Circlical\Laminas\Doctrine\Resources\SampleUser;

include_once getcwd() . '/tests/Spec/Circlical/Laminas/Doctrine/Resources/SampleUser.php';
include_once getcwd() . '/tests/Spec/Circlical/Laminas/Doctrine/Resources/Foo.php';
include_once getcwd() . '/tests/Spec/Circlical/Laminas/Doctrine/Resources/RecipeIngredient.php';

class CollectionDiffSpec extends ObjectBehavior
{
    public function it_can_deal_with_empty_collections()
    {
        $this->beConstructedWith([], []);
        $this->getShouldBeAdded()->count()->shouldBe(0);
        $this->getShouldBeRemoved()->count()->shouldBe(0);
    }

    public function it_can_add_single_elements(SampleUser $user)
    {
        $user->getId()->willReturn(1);
        $this->beConstructedWith([], [$user]);
        $this->getShouldBeAdded()->count()->shouldBe(1);
        $this->getShouldBeRemoved()->count()->shouldBe(0);
    }

    public function it_can_remove_single_elements(SampleUser $user)
    {
        $user->getId()->willReturn(1);
        $this->beConstructedWith([$user], []);
        $this->getShouldBeAdded()->count()->shouldBe(0);
        $this->getShouldBeRemoved()->count()->shouldBe(1);
    }

    public function it_can_remain_stable(SampleUser $user)
    {
        $user->getId()->willReturn(1);
        $this->beConstructedWith([$user], [$user]);
        $this->getShouldBeAdded()->count()->shouldBe(0);
        $this->getShouldBeRemoved()->count()->shouldBe(0);
    }

    public function it_works_when_both_collections_are_populated(SampleUser $a, SampleUser $b, SampleUser $c, SampleUser $d, SampleUser $e)
    {
        $a->getId()->willReturn(1);
        $b->getId()->willReturn(2);
        $c->getId()->willReturn(3);
        $d->getId()->willReturn(4);
        $e->getId()->willReturn(5);

        $this->beConstructedWith([$a, $b, $c], [$a, $c, $d, $e]);

        $added = $this->getShouldBeAdded();
        $added->contains($a)->shouldBe(false);
        $added->contains($b)->shouldBe(false);
        $added->contains($c)->shouldBe(false);
        $added->contains($d)->shouldBe(true);
        $added->contains($e)->shouldBe(true);

        $removed = $this->getShouldBeRemoved();
        $removed->contains($a)->shouldbe(false);
        $removed->contains($b)->shouldbe(true);
        $removed->contains($c)->shouldbe(false);
        $removed->contains($d)->shouldbe(false);
        $removed->contains($e)->shouldbe(false);
    }

    public function it_needs_the_interface_implementation_for_projection(Foo $newUserOne, Foo $newUserTwo)
    {
        $this->beConstructedWith([$newUserOne], [$newUserTwo], true);
        $this->shouldThrow(\RuntimeException::class)->duringInstantiation();
    }

    /**
     * @param RecipeIngredient $ingredientOne Simulates an entity that was hydrated from the EM + Doctrine
     * @param RecipeIngredient $similarIngredient Simulates an entity that was hydrated from form + Doctrine
     * @param RecipeIngredient $anotherIngredient Just another ingredient.
     */
    public function it_can_apply_projections(RecipeIngredient $ingredientOne, RecipeIngredient $similarIngredient, RecipeIngredient $anotherIngredient)
    {
        $ingredientOne->getDiffIdentifier()->willReturn('identifierOne');
        $similarIngredient->getDiffIdentifier()->willReturn('identifierOne');
        $anotherIngredient->getDiffIdentifier()->willReturn('somethingElse');

        $this->beConstructedWith([$ingredientOne], [$similarIngredient, $anotherIngredient], true);

        $ingredientOne->copyValuesFrom($similarIngredient)->shouldBeCalled();
        $this->getShouldBeAdded()->shouldHaveCount(1);
        $this->getShouldBeRemoved()->shouldHaveCount(0);
    }
}
