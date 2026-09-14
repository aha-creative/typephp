<?php

declare(strict_types=1);

use TypePHP\Exception\TypeError;

/**
 * 1. Direct parameter intersection of two sealed shapes
 *
 * @param array{id: positive-int} & array{name: non-empty-string} $data
 */
function tddComposeTwoShapes(array $data): bool
{
    return true;
}

/**
 * 2. 3-tier intersection composition
 *
 * @param array{id: positive-int} & array{name: non-empty-string} & array{active: bool} $data
 */
function tddComposeThreeShapes(array $data): bool
{
    return true;
}

/**
 * 3. Overlapping keys with refined types (Shape A has int, Shape B refines to positive-int)
 *
 * @param array{score: int, label: string} & array{score: positive-int} $payload
 */
function tddComposeOverlappingKeys(array $payload): bool
{
    return true;
}

/**
 * 4. Composed type aliases
 *
 * @phpstan-type Identifiable array{id: positive-int, created_at: non-empty-string}
 * @phpstan-type Taggable array{tags: list<non-empty-string>}
 * @phpstan-type ArticleRecord Identifiable & Taggable
 *
 * @param ArticleRecord $article
 */
function tddComposeTypeAliases(array $article): bool
{
    return true;
}

describe('Intersecting Sealed Array Shapes (Shape Composition)', function () {
    test('accepts valid array with keys from both intersected shapes', function () {
        $valid = [
            'id' => 10,
            'name' => 'Alice',
        ];

        expect(tddComposeTwoShapes($valid))->toBeTrue();
    });

    test('accepts valid array with keys across 3 intersected shapes', function () {
        $valid = [
            'id' => 10,
            'name' => 'Alice',
            'active' => true,
        ];

        expect(tddComposeThreeShapes($valid))->toBeTrue();
    });

    test('accepts valid composed type aliases (Identifiable & Taggable)', function () {
        $article = [
            'id' => 101,
            'created_at' => '2026-09-14',
            'tags' => ['php', 'typephp'],
        ];

        expect(tddComposeTypeAliases($article))->toBeTrue();
    });

    test('strictly rejects unexpected keys in merged sealed shapes', function () {
        $withExtra = [
            'id' => 10,
            'name' => 'Alice',
            'unexpected_field' => 'forbidden',
        ];

        expect(fn () => tddComposeTwoShapes($withExtra))
            ->toThrow(TypeError::class, "contains unsealed unexpected key 'unexpected_field'")
        ;
    });

    test('validates overlapping keys against the intersection of both value types', function () {
        $valid = [
            'score' => 50,
            'label' => 'good',
        ];
        expect(tddComposeOverlappingKeys($valid))->toBeTrue();

        $invalidNegativeScore = [
            'score' => -10,
            'label' => 'bad',
        ];
        expect(fn () => tddComposeOverlappingKeys($invalidNegativeScore))
            ->toThrow(TypeError::class, "['score'] must be of type (int & positive-int)")
        ;
    });

    test('rejects when any required key from any intersected shape is missing', function () {
        $missingName = [
            'id' => 10,
        ];

        expect(fn () => tddComposeTwoShapes($missingName))
            ->toThrow(TypeError::class, "is missing required key 'name'")
        ;
    });
});
