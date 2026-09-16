<?php

declare(strict_types=1);

namespace TypePHP\Tests\TypeChecking\CallablesAndIterators;

use TypePHP\Exception\TypeError;

/**
 * 1. Single by-reference parameter in callback
 *
 * @param callable(positive-int &$num): void $mutator
 */
function tddApplyScalarMutator(callable $mutator, int &$val): void
{
    $mutator($val);
}

/**
 * 2. Mixed positional parameters: value + reference
 *
 * @param callable(non-empty-string $prefix, positive-int &$id): void $mutator
 */
function tddApplyMixedByRefCallback(callable $mutator, string $prefix, int &$id): void
{
    $mutator($prefix, $id);
}

/**
 * 3. Array mutation using array_walk and wrapped callback
 *
 * @param callable(positive-int &$item, array-key $key): void $callback
 * @param list<positive-int> &$items
 */
function tddWalkListWithCallback(callable $callback, array &$items): void
{
    array_walk($items, $callback);
}

/**
 * 4. Variadic by-reference parameters in callback
 *
 * @param callable(positive-int &...$numbers): void $cb
 */
function tddVariadicByRefCallback(callable $cb, int &$a, int &$b): void
{
    $cb($a, $b);
}

describe('By-Reference Callables (callable(Type &$ref))', function () {
    describe('1. In-Place Mutation Preservation', function () {
        test('preserves in-place variable mutation when executed through wrapped callback', function () {
            $value = 10;
            $mutator = function (int &$num): void {
                $num += 50;
            };

            tddApplyScalarMutator($mutator, $value);

            expect($value)->toBe(60);
        });

        test('preserves in-place mutation on mixed value and reference callback arguments', function () {
            $id = 100;
            $formatter = function (string $prefix, int &$num): void {
                $num += 5;
            };

            tddApplyMixedByRefCallback($formatter, 'USER', $id);

            expect($id)->toBe(105);
        });

        test('preserves reference mutations in array_walk with wrapped callback', function () {
            $items = [1, 2, 3, 4];
            $multiplier = function (int &$item, mixed $key): void {
                $item *= 10;
            };

            tddWalkListWithCallback($multiplier, $items);

            expect($items)->toBe([10, 20, 30, 40]);
        });

        test('preserves reference mutations across variadic callback arguments', function () {
            $x = 10;
            $y = 20;
            $doubler = function (int &...$numbers): void {
                foreach ($numbers as &$n) {
                    $n *= 2;
                }
            };

            tddVariadicByRefCallback($doubler, $x, $y);

            expect($x)->toBe(20)
                ->and($y)->toBe(40)
            ;
        });
    });

    describe('2. Input & Post-Mutation Type Validation', function () {
        test('throws TypeError on function entry when by-ref variable violates input contract', function () {
            $invalidValue = -50;
            $mutator = function (int &$num): void {
                $num += 10;
            };

            expect(fn () => tddApplyScalarMutator($mutator, $invalidValue))
                ->toThrow(TypeError::class, 'Callback $mutator $num must be of type positive-int')
            ;

            expect($invalidValue)->toBe(-50);
        });

        test('throws TypeError when callback mutates by-ref variable to invalid value', function () {
            $value = 10;
            $badMutator = function (int &$num): void {
                $num = -999;
            };

            expect(fn () => tddApplyScalarMutator($badMutator, $value))
                ->toThrow(TypeError::class, 'must be of type positive-int')
            ;
        });
    });
});
