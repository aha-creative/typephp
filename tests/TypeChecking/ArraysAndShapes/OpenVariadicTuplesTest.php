<?php

declare(strict_types=1);

use TypePHP\Exception\TypeError;

/**
 * 1. Open tuple with single value constraint: array{string, int, ...<float>}
 *
 * @param array{string, int, ...<float>} $tuple
 */
function tddOpenTupleSingleValueType(array $tuple): array
{
    return $tuple;
}

/**
 * 2. Open tuple with array spread syntax: array{string, int, ...float[]}
 *
 * @param array{string, int, ...float[]} $tuple
 */
function tddOpenTupleArraySpread(array $tuple): array
{
    return $tuple;
}

/**
 * 3. Open tuple with list spread syntax: array{string, ...list<positive-int>}
 *
 * @param array{string, ...list<positive-int>} $data
 */
function tddOpenTupleListSpread(array $data): array
{
    return $data;
}

/**
 * 4. Bare open tuple (allows any extra elements): array{string, int, ...}
 *
 * @param array{string, int, ...} $tuple
 */
function tddBareOpenTuple(array $tuple): array
{
    return $tuple;
}

/**
 * 5. Open associative shape with single value type: array{id: positive-int, ...<string>}
 *
 * @param array{id: positive-int, ...<string>} $payload
 */
function tddOpenShapeSingleValueType(array $payload): array
{
    return $payload;
}

/**
 * 6. Open sequential list tuple: list{string, int, ...<float>}
 *
 * @param list{string, int, ...<float>} $list
 */
function tddOpenListTuple(array $list): array
{
    return $list;
}

describe('Open / Variadic Tuples and Shapes (PHPStan Spec Baseline)', function () {
    describe('1. Single Value Constraint (...<float>)', function () {
        test('accepts tuple with zero extra elements', function () {
            expect(tddOpenTupleSingleValueType(['Alice', 10]))->toBe(['Alice', 10]);
        });

        test('accepts tuple with multiple valid trailing float elements', function () {
            expect(tddOpenTupleSingleValueType(['Alice', 10, 1.5, 2.5, 3.5]))
                ->toBe(['Alice', 10, 1.5, 2.5, 3.5])
            ;
        });

        test('rejects tuple when trailing element violates float constraint', function () {
            expect(fn () => tddOpenTupleSingleValueType(['Alice', 10, 1.5, 'not_a_float']))
                ->toThrow(TypeError::class, "['3'] must be of type float")
            ;
        });
    });

    describe('2. Array Spread Syntax (...float[])', function () {
        test('accepts tuple with zero extra elements using array spread', function () {
            expect(tddOpenTupleArraySpread(['Bob', 20]))->toBe(['Bob', 20]);
        });

        test('unwraps float[] so trailing elements are validated as individual floats', function () {
            expect(tddOpenTupleArraySpread(['Bob', 20, 1.5, 2.5]))->toBe(['Bob', 20, 1.5, 2.5]);
        });

        test('rejects trailing element violating unwrapped float constraint', function () {
            expect(fn () => tddOpenTupleArraySpread(['Bob', 20, 1.5, 'invalid']))
                ->toThrow(TypeError::class, "['3'] must be of type float")
            ;
        });
    });

    describe('3. List Spread Syntax (...list<positive-int>)', function () {
        test('unwraps list<positive-int> so trailing elements are validated as positive integers', function () {
            expect(tddOpenTupleListSpread(['tag_primary', 10, 20, 30]))
                ->toBe(['tag_primary', 10, 20, 30])
            ;
        });

        test('rejects trailing element violating positive-int constraint', function () {
            expect(fn () => tddOpenTupleListSpread(['tag_primary', 10, -5, 30]))
                ->toThrow(TypeError::class, "['2'] must be of type positive-int")
            ;
        });
    });

    describe('4. Bare ... Open Tuple (array{string, int, ...})', function () {
        test('accepts tuple with zero extra elements', function () {
            expect(tddBareOpenTuple(['Alice', 10]))->toBe(['Alice', 10]);
        });

        test('accepts arbitrary trailing elements of any type', function () {
            $data = ['Alice', 10, true, 3.14, new stdClass(), ['nested']];
            expect(tddBareOpenTuple($data))->toBe($data);
        });

        test('still strictly validates the fixed leading elements', function () {
            expect(fn () => tddBareOpenTuple([123, 10, 'extra']))
                ->toThrow(TypeError::class, "['0'] must be of type string")
            ;

            expect(fn () => tddBareOpenTuple(['Alice', 'not_an_int', 'extra']))
                ->toThrow(TypeError::class, "['1'] must be of type int")
            ;
        });
    });

    describe('5. Associative Open Shape with Single Value Type (array{id: int, ...<string>})', function () {
        test('accepts shape with extra string values under arbitrary keys', function () {
            $payload = [
                'id' => 1,
                'name' => 'Alice',
                'role' => 'admin',
                'dept' => 'engineering',
            ];

            expect(tddOpenShapeSingleValueType($payload))->toBe($payload);
        });

        test('rejects extra element when value is not a string', function () {
            $badPayload = [
                'id' => 1,
                'name' => 'Alice',
                'count' => 50,
            ];

            expect(fn () => tddOpenShapeSingleValueType($badPayload))
                ->toThrow(TypeError::class, "['count'] must be of type string")
            ;
        });
    });

    describe('6. Open Sequential List Tuples (list{string, int, ...<float>})', function () {
        test('accepts sequential list with trailing floats', function () {
            expect(tddOpenListTuple(['test', 1, 1.1, 2.2]))->toBe(['test', 1, 1.1, 2.2]);
        });

        test('rejects open list tuple when passed non-sequential associative keys', function () {
            $associative = [
                0 => 'test',
                1 => 1,
                'non_sequential' => 1.5,
            ];

            expect(fn () => tddOpenListTuple($associative))
                ->toThrow(TypeError::class, 'must be a list')
            ;
        });
    });
});
