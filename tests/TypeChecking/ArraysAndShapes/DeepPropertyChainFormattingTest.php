<?php

declare(strict_types=1);

use TypePHP\Exception\TypeError;

class FixtureGeo
{
    public string $city;

    public string $country = 'PH';

    public function __construct(string $city)
    {
        $this->city = $city;
    }
}

class FixtureAddress
{
    public FixtureGeo $geo;

    public function __construct(FixtureGeo $geo)
    {
        $this->geo = $geo;
    }
}

class FixtureCompany
{
    public FixtureAddress $address;

    public function __construct(FixtureAddress $address)
    {
        $this->address = $address;
    }
}

class FixtureUser
{
    public FixtureCompany $company;

    public function __construct(FixtureCompany $company)
    {
        $this->company = $company;
    }
}

class FixtureUninitializedGeo
{
    public string $city;
}

/**
 * 1. 4-level deep stdClass chain
 *
 * @param object{company: object{department: object{lead: object{name: non-empty-string}}}} $user
 */
function testFourLevelStdClassChain(object $user): bool
{
    return true;
}

/**
 * 2. 4-level deep typed class chain
 *
 * @param object{company: object{address: object{geo: object{city: non-empty-string}}}} $user
 */
function testFourLevelTypedClassChain(object $user): bool
{
    return true;
}

/**
 * 3. Mixed hybrid chain (Object -> List -> Object -> String)
 *
 * @param object{branches: list<object{manager: object{email: non-empty-string}}>} $org
 */
function testMixedObjectArrayChain(object $org): bool
{
    return true;
}

describe('Deep Property Chain Error Formatting (3+ Levels Deep)', function () {
    describe('4-Level stdClass Chain ($user->company->department->lead->name)', function () {
        test('passes when all 4 levels are valid', function () {
            $user = (object)[
                'company' => (object)[
                    'department' => (object)[
                        'lead' => (object)[
                            'name' => 'Alice',
                        ],
                    ],
                ],
            ];

            expect(testFourLevelStdClassChain($user))->toBeTrue();
        });

        test('formats error breadcrumbs across 4 levels of stdClass', function () {
            $user = (object)[
                'company' => (object)[
                    'department' => (object)[
                        'lead' => (object)[
                            'name' => '', // Fails non-empty-string
                        ],
                    ],
                ],
            ];

            expect(fn () => testFourLevelStdClassChain($user))
                ->toThrow(
                    TypeError::class,
                    "testFourLevelStdClassChain(): Argument \$user->company->department->lead->name must be of type non-empty-string, empty string ('') given"
                )
            ;
        });

        test('formats deep missing property in 4-level stdClass chain', function () {
            $user = (object)[
                'company' => (object)[
                    'department' => (object)[
                        'lead' => (object)[
                            // 'name' is missing
                        ],
                    ],
                ],
            ];

            expect(fn () => testFourLevelStdClassChain($user))
                ->toThrow(
                    TypeError::class,
                    "testFourLevelStdClassChain(): Argument \$user->company->department->lead is missing required property 'name'"
                )
            ;
        });
    });

    describe('4-Level Typed Class Chain ($user->company->address->geo->city)', function () {
        test('formats error breadcrumbs across 4 levels of real classes', function () {
            $geo = new FixtureGeo('');
            $address = new FixtureAddress($geo);
            $company = new FixtureCompany($address);
            $user = new FixtureUser($company);

            expect(fn () => testFourLevelTypedClassChain($user))
                ->toThrow(
                    TypeError::class,
                    "testFourLevelTypedClassChain(): Argument \$user->company->address->geo->city must be of type non-empty-string, empty string ('') given"
                )
            ;
        });

        test('formats deep uninitialized property in long typed class chain', function () {
            $uninitGeo = new FixtureUninitializedGeo();
            $company = (object)[
                'address' => (object)[
                    'geo' => $uninitGeo,
                ],
            ];
            $user = (object)[
                'company' => $company,
            ];

            expect(fn () => testFourLevelTypedClassChain($user))
                ->toThrow(
                    TypeError::class,
                    "testFourLevelTypedClassChain(): Argument \$user->company->address->geo property 'city' is uninitialized"
                )
            ;
        });
    });

    describe('Mixed Hybrid Chain ($org->branches[0]->manager->email)', function () {
        test('formats mixed object and array breadcrumbs seamlessly', function () {
            $org = (object)[
                'branches' => [
                    (object)[
                        'manager' => (object)[
                            'email' => 'valid@test.com',
                        ],
                    ],
                    (object)[
                        'manager' => (object)[
                            'email' => '',
                        ],
                    ],
                ],
            ];

            expect(fn () => testMixedObjectArrayChain($org))
                ->toThrow(
                    TypeError::class,
                    "testMixedObjectArrayChain(): Argument \$org->branches[1]->manager->email must be of type non-empty-string, empty string ('') given"
                )
            ;
        });
    });
});
