<?php

declare(strict_types=1);

use TypePHP\Exception\TypeError;

class PropertyMutationFixture
{
    /**
     * @var positive-int
     */
    public int $score = 10;

    /**
     * @var positive-int
     */
    public static int $staticScore = 10;

    public function multiplyScore(int $factor): void
    {
        $this->score *= $factor;
    }

    public function decrementScore(): void
    {
        $this->score--;
    }

    public static function multiplyStaticScore(int $factor): void
    {
        self::$staticScore *= $factor;
    }

    public static function decrementStaticScore(): void
    {
        self::$staticScore--;
    }
}

describe('Compound Operator Assignments and Inc/Dec Validations', function () {
    describe('Local Variable Compound Assignments (*=, +=, -=, .=, ??=)', function () {
        test('validates *= compound assignment against positive-int contract', function () {
            /** @var positive-int */
            $x = 10;

            $x *= 2;
            expect($x)->toBe(20);

            expect(fn () => $x *= -1)
                ->toThrow(TypeError::class, 'Variable $x must be of type positive-int, negative int (-20) given')
            ;
        });

        test('validates += and -= compound assignments against integer bounds (int<1, 10>)', function () {
            /** @var int<1, 10> */
            $count = 5;

            $count += 3;
            expect($count)->toBe(8);

            expect(fn () => $count += 5)
                ->toThrow(TypeError::class, '<= 10')
            ;

            expect(fn () => $count -= 10)
                ->toThrow(TypeError::class, '>= 1')
            ;
        });

        test('validates .= string concatenation against non-empty-string contract', function () {
            /** @var non-empty-string */
            $str = 'hello';

            $str .= ' world';
            expect($str)->toBe('hello world');
        });
    });

    describe('Local Variable Increment and Decrement (++, --)', function () {
        test('validates post-decrement and pre-decrement against positive-int contract', function () {
            /** @var positive-int */
            $val = 1;

            expect(fn () => $val--)
                ->toThrow(TypeError::class, 'Variable $val must be of type positive-int, zero int (0) given')
            ;

            /** @var positive-int */
            $preVal = 1;

            expect(fn () => --$preVal)
                ->toThrow(TypeError::class, 'Variable $preVal must be of type positive-int, zero int (0) given')
            ;
        });

        test('validates post-increment and pre-increment against int-range upper bound', function () {
            /** @var int<1, 5> */
            $range = 5;

            expect(fn () => $range++)
                ->toThrow(TypeError::class, '<= 5')
            ;

            /** @var int<1, 5> */
            $preRange = 5;

            expect(fn () => ++$preRange)
                ->toThrow(TypeError::class, '<= 5')
            ;
        });
    });

    describe('Class Property Compound Assignments & Inc/Dec', function () {
        test('validates property *= compound assignment against @var positive-int', function () {
            $fixture = new PropertyMutationFixture();

            $fixture->multiplyScore(2);
            expect($fixture->score)->toBe(20);

            expect(fn () => $fixture->multiplyScore(-1))
                ->toThrow(TypeError::class, 'Property PropertyMutationFixture::$score must be of type positive-int')
            ;
        });

        test('validates property -- decrement against @var positive-int', function () {
            $fixture = new PropertyMutationFixture();
            $fixture->score = 1;

            expect(fn () => $fixture->decrementScore())
                ->toThrow(TypeError::class, 'Property PropertyMutationFixture::$score must be of type positive-int')
            ;
        });

        test('validates static property *= compound assignment', function () {
            PropertyMutationFixture::$staticScore = 10;
            PropertyMutationFixture::multiplyStaticScore(3);
            expect(PropertyMutationFixture::$staticScore)->toBe(30);

            expect(fn () => PropertyMutationFixture::multiplyStaticScore(-1))
                ->toThrow(TypeError::class, 'Property PropertyMutationFixture::$staticScore must be of type positive-int')
            ;
        });

        test('validates static property -- decrement', function () {
            PropertyMutationFixture::$staticScore = 1;

            expect(fn () => PropertyMutationFixture::decrementStaticScore())
                ->toThrow(TypeError::class, 'Property PropertyMutationFixture::$staticScore must be of type positive-int')
            ;
        });
    });
});
