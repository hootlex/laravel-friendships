# Laravel 5 Friendships
[![Build Status](https://travis-ci.org/hootlex/laravel-friendships.svg?branch=v1.0.17)](https://travis-ci.org/hootlex/laravel-friendships) [![Code Climate](https://codeclimate.com/github/hootlex/laravel-friendships/badges/gpa.svg)](https://codeclimate.com/github/hootlex/laravel-friendships) [![Test Coverage](https://codeclimate.com/github/hootlex/laravel-friendships/badges/coverage.svg)](https://codeclimate.com/github/hootlex/laravel-friendships/coverage) [![Total Downloads](https://img.shields.io/packagist/dt/hootlex/laravel-friendships.svg?style=flat)](https://packagist.org/packages/hootlex/laravel-friendships) [![Version](https://img.shields.io/packagist/v/hootlex/laravel-friendships.svg?style=flat)](https://packagist.org/packages/hootlex/laravel-friendships) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)


This package gives Eloquent models the ability to manage their friendships.
You can easily design a Facebook like Friend System.

##Models can:
- Send Friend Requests
- Accept Friend Requests
- Deny Friend Requests
- Block Another Model
- Group Friends

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
Publish config and migrations

```
php artisan vendor:publish --provider="Hootlex\Friendships\FriendshipsServiceProvider"
```
Configure the published config in
```
config\friendships.php
```
Finally, migrate the database
```
php artisan migrate
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

#### Check if Model has pending a friend request with another Model
```php
$user->hasPendingFriendRequest($recipient);
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

#### Get the number of mutual Friends with another user
```php
$user->getMutualFriendsCount($otherUser);
```

## Friends
To get a collection of friend models (ex. User) use the following methods:
#### Get Friends
```php
$user->getFriends();
```

#### Get Friends Paginated
```php
$user->getFriends($perPage = 20);
```

#### Get Friends of Friends
```php
$user->getFriendsOfFriends($perPage = 20);
```

#### Collection of Friends in specific group paginated:
```php
$user->getFriends($perPage = 20, $group_name);
```

#### Get mutual Friends with another user
```php
$user->getMutualFriends($otherUser, $perPage = 20);
```

## Friend groups
The friend groups are defined in the `config/friendships.php` file.
The package comes with a few default groups.
To modify them, or add your own, you need to specify a `slug` and a `key`.

```php
// config/friendships.php
...
'groups' => [
    'acquaintances' => 0,
    'close_friends' => 1,
    'family' => 2
]
```

Since you've configured friend groups, you can group/ungroup friends using the following methods.

#### Group a Friend
```php
$user->groupFriend($friend, $group_name);
```

#### Remove a Friend from family group
```php
$user->ungroupFriend($friend, 'family');
```

#### Remove a Friend from all groups
```php
$user->ungroupFriend($friend);
```

#### Get the number of Friends in specific group
```php
$user->getFriendsCount($group_name);
```

### To filter `friendships` by group you can pass a group slug.
```php
$user->getAllFriendships($group_name);
$user->getAcceptedFriendships($group_name);
$user->getPendingFriendships($group_name);
...
```

## Contributing
See the [CONTRIBUTING](CONTRIBUTING.md) guide.
