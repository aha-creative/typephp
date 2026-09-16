<?php

declare(strict_types=1);

namespace TypePHP\Tests\Internal\Checker;

use TypePHP\Internal\Checker\ParamOutChecker;
use TypePHP\Internal\Diagnostic\ErrorMessage;
use TypePHP\Internal\Generics\TemplateManager;
use TypePHP\Internal\Util\Config;
use TypePHP\Internal\Validator\TypeValidatorRegistry;
use TypePHP\Tests\Fixtures\Domain\Animal;
use TypePHP\Tests\Fixtures\Domain\Car;
use TypePHP\Tests\Fixtures\Domain\Dog;

/**
 * Fixture: Scalar @param-out
 *
 * @param mixed &$id
 * @param-out positive-int $id
 */
function internalParamOutScalarFixture(mixed &$id): void
{
}

/**
 * Fixture: Generic @param-out
 *
 * @template T of Animal
 *
 * @param T $pet
 * @param mixed &$out
 * @param-out T $out
 */
function internalParamOutGenericFixture(Animal $pet, mixed &$out): void
{
}

describe('ParamOutChecker Unit Tests', function () {
    beforeEach(function () {
        Config::reset();
        ParamOutChecker::reset();
    });

    afterEach(function () {
        Config::reset();
        ParamOutChecker::reset();
    });

    test('returns null when mutated by-ref value satisfies contract', function () {
        $registry = new TypeValidatorRegistry();

        $result = ParamOutChecker::checkParamOut(
            'TypePHP\Tests\Internal\Checker\internalParamOutScalarFixture',
            'id',
            42,
            null,
            $registry
        );

        expect($result)->toBeNull();
    });

    test('returns ErrorMessage when mutated by-ref value violates contract', function () {
        $registry = new TypeValidatorRegistry();

        $result = ParamOutChecker::checkParamOut(
            'TypePHP\Tests\Internal\Checker\internalParamOutScalarFixture',
            'id',
            -50,
            null,
            $registry
        );

        expect($result)->toBeInstanceOf(ErrorMessage::class)
            ->and($result->getMessage())->toContain('Argument &$id (param-out) must be of type positive-int')
        ;
    });

    test('substitutes generic templates in @param-out contracts', function () {
        $registry = new TypeValidatorRegistry();
        $target = 'TypePHP\Tests\Internal\Checker\internalParamOutGenericFixture';

        TemplateManager::bindTemplate($target, null, 'T', new \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode(Dog::class));

        $validResult = ParamOutChecker::checkParamOut($target, 'out', new Dog(), null, $registry);
        expect($validResult)->toBeNull();

        $invalidResult = ParamOutChecker::checkParamOut($target, 'out', new Car(), null, $registry);
        expect($invalidResult)->toBeInstanceOf(ErrorMessage::class)
            ->and($invalidResult->getMessage())->toContain('TypePHP\Tests\Fixtures\Domain\Dog')
        ;
    });

    test('short-circuits via $noParamOutContractCache for methods without param-out', function () {
        $registry = new TypeValidatorRegistry();

        $res1 = ParamOutChecker::checkParamOut('nonExistentFunc', 'missing', 10, null, $registry);
        expect($res1)->toBeNull()
            ->and(ParamOutChecker::$noParamOutContractCache)->toHaveKey('nonExistentFunc')
        ;

        $res2 = ParamOutChecker::checkParamOut('nonExistentFunc', 'missing', 10, null, $registry);
        expect($res2)->toBeNull();
    });
});