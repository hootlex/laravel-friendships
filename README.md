# Laravel 5 Friendships [![Build Status](https://travis-ci.org/hootlex/laravel-friendships.svg?branch=v1.0.17)](https://travis-ci.org/hootlex/laravel-friendships)  [![Total Downloads](https://img.shields.io/packagist/dt/hootlex/laravel-friendships.svg?style=flat)](https://packagist.org/packages/hootlex/laravel-friendships) [![Version](https://img.shields.io/packagist/v/hootlex/laravel-friendships.svg?style=flat)](https://packagist.org/packages/hootlex/laravel-friendships) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)


This package gives Eloquent models the ability to manage their friendships.
You can easily design a Facebook like Friend System.

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
php artisan vendor:publish --provider="Hootlex\Friendships\FriendshipsServiceProvider" && php artisan migrate
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
[Check the Test file to see the package in action](https://github.com/hootlex/laravel-friendships/blob/master/tests/FriendshipsTest.php)

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

#### Check if Model has a pending friend request from another Model
```php
$user->hasFriendRequestFrom($recipient);
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
// (return a collection of friendships)
$user->getAllFriendships();
// or with the new method (return query builder):
$user->requests();
```

#### Get a list of pending Friendships
```php
// (return a collection of friendships)
$user->getPendingFriendships();
// or with the new method (return query builder):
$user->requests()->pending();
```

#### Get a list of accepted Friendships
```php
// (return a collection of friendships)
$user->getAcceptedFriendships();
// or with the new method (return query builder):
$user->requests()->accepted();
```

#### Get a list of denied Friendships
```php
// (return a collection of friendships)
$user->getDeniedFriendships();
// or with the new method (return query builder):
$user->requests()->denied();
```

#### Get a list of blocked Friendships
```php
// (return a collection of friendships)
$user->getBlockedFriendships();
// or with the new method (return query builder):
$user->requests()->blocked();
```

#### Get a list of pending Friend Requests
```php
// (return a collection of friendships)
$user->getFriendRequests();
// or with the new method (return query builder):
$user->requests(Direction::INCOMING)->pending();
```

#### Get the number of Friends 
```php
$user->getFriendsCount();
```


### To get a collection of friend models (ex. User) use the following methods:
#### Get Friends
```php
$user->getFriends();
// or with the new method (return query builder):
$user->friendships();
```

#### Get Friends Paginated
```php
$user->getFriends($perPage = 20);
```

#### Get Friends of Friends
```php
$user->getFriendsOfFriends($perPage = 20);
```
