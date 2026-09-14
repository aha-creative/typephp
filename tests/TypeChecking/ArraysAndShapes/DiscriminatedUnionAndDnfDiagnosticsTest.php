<?php

declare(strict_types=1);

use TypePHP\Exception\TypeError;

class TddDnfCountableCollection extends ArrayIterator implements Countable
{
}

/**
 * 1. Discriminated Union with string literal discriminators
 *
 * @param array{type: 'click', x: int, y: int}
 *      | array{type: 'hover', element: non-empty-string}
 *      | array{type: 'scroll', offset: positive-int} $event
 */
function tddProcessDiscriminatedEvent(array $event): bool
{
    return true;
}

/**
 * 2. Discriminated Union with object shapes
 *
 * @param object{kind: 'user', id: positive-int}
 *      | object{kind: 'bot', user_agent: non-empty-string} $actor
 */
function tddProcessDiscriminatedObject(object $actor): bool
{
    return true;
}

/**
 * 3. DNF combining interfaces and object shapes
 *
 * @param (Countable&object{id: positive-int})|(Iterator&object{code: non-empty-string}) $payload
 */
function tddProcessDnfWithShape(mixed $payload): bool
{
    return true;
}

describe('Discriminated Union & DNF Diagnostic Precision (TDD Baseline)', function () {
    describe('Discriminated Union Best-Match Branch Selection', function () {
        test('passes when matching branch is completely valid', function () {
            expect(tddProcessDiscriminatedEvent(['type' => 'click', 'x' => 10, 'y' => 20]))->toBeTrue();
            expect(tddProcessDiscriminatedEvent(['type' => 'hover', 'element' => 'btn-submit']))->toBeTrue();
            expect(tddProcessDiscriminatedEvent(['type' => 'scroll', 'offset' => 100]))->toBeTrue();
        });

        test('targets the hover branch when type is hover, instead of blaming click branch', function () {
            $invalidHoverEvent = [
                'type' => 'hover',
                'element' => '',
            ];

            expect(fn () => tddProcessDiscriminatedEvent($invalidHoverEvent))
                ->toThrow(TypeError::class, "['element'] must be of type non-empty-string")
            ;
        });

        test('targets the scroll branch when type is scroll, instead of blaming click branch', function () {
            $invalidScrollEvent = [
                'type' => 'scroll',
                'offset' => -50,
            ];

            expect(fn () => tddProcessDiscriminatedEvent($invalidScrollEvent))
                ->toThrow(TypeError::class, "['offset'] must be of type positive-int")
            ;
        });

        test('falls back to full union error when discriminator matches no branch at all', function () {
            $unknownEvent = [
                'type' => 'custom_unknown',
                'foo' => 'bar',
            ];

            expect(fn () => tddProcessDiscriminatedEvent($unknownEvent))
                ->toThrow(TypeError::class, 'must be of type (array{type: \'click\', x: int, y: int} | array{type: \'hover\', element: non-empty-string} | array{type: \'scroll\', offset: positive-int})')
            ;
        });
    });

    describe('Discriminated Object Shapes', function () {
        test('targets the bot branch when kind is bot', function () {
            $invalidBot = (object)[
                'kind' => 'bot',
                'user_agent' => '',
            ];

            expect(fn () => tddProcessDiscriminatedObject($invalidBot))
                ->toThrow(TypeError::class, '->user_agent must be of type non-empty-string')
            ;
        });
    });

    describe('DNF with Shapes and Intersections', function () {
        test('accepts valid object matching first DNF branch (Countable & object shape)', function () {
            $val = new class () implements Countable {
                public int $id = 10;

                public function count(): int
                {
                    return 1;
                }
            };

            expect(tddProcessDnfWithShape($val))->toBeTrue();
        });

        test('rejects object failing both DNF branches', function () {
            $invalid = new class () implements Countable {
                public function count(): int
                {
                    return 0;
                }
            };

            expect(fn () => tddProcessDnfWithShape($invalid))
                ->toThrow(TypeError::class)
            ;
        });
    });
});
