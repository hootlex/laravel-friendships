# Laravel 5 Friendships [![Build Status](https://travis-ci.org/hootlex/laravel-friendships.svg?branch=master)](https://travis-ci.org/hootlex/laravel-friendships) [![Version](https://img.shields.io/packagist/v/hootlex/laravel-friendships.svg?style=flat)](https://packagist.org/packages/hootlex/laravel-friendships)  [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)

This package gives Eloqent models the ability to manage their friendships.
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

#### Get All Friends(accepted) (It returns a collection of friend models not friendships, for example User)
```php
$user->getAllFriends();
```

#### Get Friends(accepted) with Limit 
```php
$user->getFriendsLimited($limit);
Default $limit = 0, will return all friends
```
##### Use Case
```php
$user->getFriendsLimited(5);
```
This will return only 5 friends.

#### Get Friends(accepted) with Pagination 
```php
$user->getFriendsWithPagination($perPage);
Default $perPage = 0, will return all friends
```

##### Use Case
```php
$friends = $user->getFriendsWithPagination(5);
```
This will return a paginator object. To render links
```
Laravel >5.0: Use ->render();
Laravel >5.2: Use ->links();
```
##### View
```html
@foreach($friends as $friend)
	<p>{{ $friend->name }}</p>
@endforeach

{!! $friends->links() !!}
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

#### Get the number of Friends 
```php
$user->getFriendsCount();
```