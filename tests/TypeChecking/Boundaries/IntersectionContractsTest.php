<?php

declare(strict_types=1);

use TypePHP\Exception\TypeError;

class TddOnlyCountable implements Countable
{
    public function count(): int
    {
        return 5;
    }
}

class TddCountableAndIterator implements Countable, Iterator
{
    private int $pos = 0;

    private array $data = [1, 2, 3];

    public function count(): int
    {
        return \count($this->data);
    }

    public function current(): mixed
    {
        return $this->data[$this->pos];
    }

    public function key(): mixed
    {
        return $this->pos;
    }

    public function next(): void
    {
        $this->pos++;
    }

    public function rewind(): void
    {
        $this->pos = 0;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->pos]);
    }
}

/**
 * 1. Template bounded by intersection (@template T of Countable&Iterator)
 *
 * @template T of \Countable&\Iterator
 *
 * @param T $thing
 *
 * @return T
 */
function tddProcessIterableTemplate(mixed $thing): mixed
{
    return $thing;
}

/**
 * 2. Direct intersection parameter (@param Countable&Iterator $thing)
 *
 * @param Countable&Iterator $thing
 */
function tddProcessDirectIntersection(mixed $thing): bool
{
    return true;
}

/**
 * 3. Array shape containing an intersection
 *
 * @param array{collection: Countable&ArrayAccess} $payload
 */
function tddProcessShapeWithIntersection(array $payload): bool
{
    return true;
}

/**
 * 4. Intersection with deep object shape (stdClass & object{id: positive-int})
 *
 * @param stdClass&object{id: positive-int} $entity
 */
function tddProcessIntersectionWithDeepShape(object $entity): bool
{
    return true;
}

describe('Intersection Contract Error Diagnostics (TDD Baseline)', function () {
    test('passes when object satisfies all intersection members', function () {
        $valid = new TddCountableAndIterator();

        expect(tddProcessIterableTemplate($valid))->toBe($valid);
        expect(tddProcessDirectIntersection($valid))->toBeTrue();
    });

    test('reports full intersection type when template bound intersection is violated', function () {
        $invalid = new TddOnlyCountable();

        expect(fn () => tddProcessIterableTemplate($invalid))
            ->toThrow(TypeError::class, 'must be of type (Countable & Iterator), TddOnlyCountable given')
        ;
    });

    test('reports full intersection type when direct intersection parameter is violated', function () {
        $invalid = new TddOnlyCountable();

        expect(fn () => tddProcessDirectIntersection($invalid))
            ->toThrow(TypeError::class, 'must be of type (Countable & Iterator), TddOnlyCountable given')
        ;
    });

    test('reports full intersection type for nested shape properties', function () {
        $payload = ['collection' => new TddOnlyCountable()];

        expect(fn () => tddProcessShapeWithIntersection($payload))
            ->toThrow(TypeError::class, "['collection'] must be of type (Countable & ArrayAccess)")
        ;
    });

    test('preserves deep error bubbling inside intersection object shape', function () {
        $entity = new stdClass();
        $entity->id = -5;

        expect(fn () => tddProcessIntersectionWithDeepShape($entity))
            ->toThrow(TypeError::class, '->id must be of type positive-int')
        ;
    });
});
