# Laravel Friendships [![GitHub release](https://img.shields.io/github/release/hootlex/Laravel-Friendships.svg?style=flat)](https://packagist.org/packages/hootlex/laravel-friendship) [![Build Status](https://travis-ci.org/hootlex/laravel-friendships.svg?branch=master)](https://travis-ci.org/hootlex/laravel-friendships)

This package gives Eloqent models the ability to manage their friendships.
##Models can:
- Send Friend Requests
- Accept Friend Requests
- Deny Friend Requests
- Block Another Model

## Installation

First, install the package through Composer.

```php
composer require hootlex/laravel-friendships
```

Then include the service provider inside `config/app.php`.

```php
'providers' => [
    ...
    Hootlex\Friendships\FriendshipsServiceProvider::class,
    ...
];
```
Lastly you need to publish the migration and migrate the database

```
php artisan vendor:publish --provider="Hootlex\Friendships\FriendshipsServiceProvider" && artisan migrate
```
## Setup a Model
```php
use Hootlex\Friendships\Traits\Friendable;
class User extends Model
{
    use Friendable;
    ...
}
```

## How to use 
[Check the Test file to see the package in action](https://github.com/hootlex/laravel-friendships/blob/master/tests/FriedshipsTest.php)

#### Send a Friend Request
```php
$user->befriend($recipient);
```

#### Accept a Friend Request
```php
$user->acceptFriendRequest($recipient);
```

#### Deny a Friend Request
```php
$user->denyFriendRequest($recipient);
```

#### Remove Friend
```php
$user->unfriend($recipient);
```

#### Block a Model
```php
$user->blockFriend($recipient);
```

#### Unblock a Model
```php
$user->unblockFriend($recipient);
```

#### Check if Model is Friend with another Model
```php
$user->isFriendWith($recipient);
```

#### Check if Model has blocked another Model
```php
$user->hasBlocked($recipient);
```

#### Check if Model is blocked by another Model
```php
$user->isBlockedBy($recipient);
```

#### Get a single friendship
```php
$user->getFriendship($recipient);
```

#### Get a list of all Friendships
```php
$user->getAllFriendships();
```

#### Get a list of pending Friendships
```php
$user->getPendingFriendships();
```

#### Get a list of accepted Friendships
```php
$user->getAcceptedFriendships();
```

#### Get a list of denied Friendships
```php
$user->getDeniedFriendships();
```

#### Get a list of blocked Friendships
```php
$user->getBlockedFriendships();
```

#### Get a list of pending Friend Requests
```php
$user->getFriendRequests();
```
