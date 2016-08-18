<?php

namespace Hootlex\Friendships\Traits;

use Hootlex\Friendships\Models\Friendship;
use Hootlex\Friendships\Models\FriendFriendshipGroups;
use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;
use Event;

/**
 * Class Friendable
 * @package Hootlex\Friendships\Traits
 */
trait Friendable
{
    /**
     * @param Model $recipient
     *
     * @return \Hootlex\Friendships\Models\Friendship|false
     */
    public function befriend(Model $recipient)
    {

        if (!$this->canBefriend($recipient)) {
            return false;
        }

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->friendshipRequests()->save($friendship);
      
        Event::fire('friendships.sent', [$this, $recipient]);

        return $friendship;

    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {
        Event::fire('friendships.cancelled', [$this, $recipient]);

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasFriendRequestFrom(Model $recipient)
    {
        return $this->findFriendship($recipient)->whereSender($recipient)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasSentFriendRequestTo(Model $recipient)
    {
        return Friendship::whereRecipient($recipient)->whereSender($this)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isFriendWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool|int
     */
    public function acceptFriendRequest(Model $recipient)
    {
        Event::fire('friendships.accepted', [$this, $recipient]);
      
        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::ACCEPTED,
        ]);
    }

    /**
     * @param Model $recipient
     *
     * @return bool|int
     */
    public function denyFriendRequest(Model $recipient)
    {
        Event::fire('friendships.denied', [$this, $recipient]);
      
        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::DENIED,
        ]);
    }


    /**
     * @param Model $friend
     * @param $groupSlug
     * @return bool
     */
    public function groupFriend(Model $friend, $groupSlug)
    {

        $friendship       = $this->findFriendship($friend)->whereStatus(Status::ACCEPTED)->first();
        $groupsAvailable = config('friendships.groups', []);

        if (!isset($groupsAvailable[$groupSlug]) || empty($friendship)) {
                    return false;
        }

        $group = $friendship->groups()->firstOrCreate([
            'friendship_id' => $friendship->id,
            'group_id'      => $groupsAvailable[$groupSlug],
            'friend_id'     => $friend->getKey(),
            'friend_type'   => $friend->getMorphClass(),
        ]);

        return $group->wasRecentlyCreated;

    }

    /**
     * @param Model $friend
     * @param $groupSlug
     * @return bool
     */
    public function ungroupFriend(Model $friend, $groupSlug = '')
    {

        $friendship       = $this->findFriendship($friend)->first();
        $groupsAvailable = config('friendships.groups', []);

        if (empty($friendship)) {
                    return false;
        }

        $where = [
            'friendship_id' => $friendship->id,
            'friend_id'     => $friend->getKey(),
            'friend_type'   => $friend->getMorphClass(),
        ];

        if ('' !== $groupSlug && isset($groupsAvailable[$groupSlug])) {
            $where['group_id'] = $groupsAvailable[$groupSlug];
        }

        $result = $friendship->groups()->where($where)->delete();

        return $result;

    }

    /**
     * @param Model $recipient
     *
     * @return \Hootlex\Friendships\Models\Friendship
     */
    public function blockFriend(Model $recipient)
    {
        // if there is a friendship between the two users and the sender is not blocked
        // by the recipient user then delete the friendship
        if (!$this->isBlockedBy($recipient)) {
            $this->findFriendship($recipient)->delete();
        }

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);
      
        Event::fire('friendships.blocked', [$this, $recipient]);

        return $this->friendshipRequests()->save($friendship);
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        Event::fire('friendships.unblocked', [$this, $recipient]);
      
        return $this->findFriendship($recipient)->whereSender($this)->delete();
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @param Model $recipient
     *
     * @return \Hootlex\Friendships\Models\Friendship
     */
    public function getFriendship(Model $recipient)
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAllFriendships($groupSlug = '')
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->findFriendships(null, $groupSlug)->get();
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getPendingFriendships($groupSlug = '')
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->findFriendships(Status::PENDING, $groupSlug)->get();
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedFriendships($groupSlug = '')
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->findFriendships(Status::ACCEPTED, $groupSlug)->get();
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedFriendships()
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->findFriendships(Status::DENIED)->get();
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFriendships()
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->findFriendships(Status::BLOCKED)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friendshipRequests()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendRequests()
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return Friendship::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @deprecated Will be removed in version 2
     *
     * @param int $perPage Number
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0, $groupSlug = '')
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->getOrPaginate($this->getFriendsQueryBuilder($groupSlug), $perPage);
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @deprecated Will be removed in version 2
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMutualFriends(Model $other, $perPage = 0)
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other), $perPage);
    }

    /**
     * Get the number of friends
     *
     * @deprecated Will be removed in version 2
     *
     * @return integer
     */
    public function getMutualFriendsCount($other)
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @deprecated Will be removed in version 2
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends($perPage = 0)
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder(), $perPage);

    }


    /**
     * Get the number of friends
     *
     * @param string $groupSlug
     * @deprecated Will be removed in version 2
     *
     * @return integer
     */
    public function getFriendsCount($groupSlug = '')
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        $friendsCount = $this->findFriendships(Status::ACCEPTED, $groupSlug)->count();
        return $friendsCount;
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function canBefriend($recipient)
    {
        // if user has Blocked the recipient and changed his mind
        // he can send a friend request after unblocking
        if ($this->hasBlocked($recipient)) {
            $this->unblockFriend($recipient);
            return true;
        }

        // if sender has a friendship with the recipient return false
        if ($friendship = $this->getFriendship($recipient)) {
            // if previous friendship was Denied then let the user send fr
            if ($friendship->status != Status::DENIED) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendship(Model $recipient)
    {
        return Friendship::betweenModels($this, $recipient);
    }

    /**
     * @param $status
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null, $groupSlug = '')
    {

        $query = Friendship::where(function ($query) {
            $query->where(function ($q) {
                $q->whereSender($this);
            })->orWhere(function ($q) {
                $q->whereRecipient($this);
            });
        })->whereGroup($this, $groupSlug);

        //if $status is passed, add where clause
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder($groupSlug = '')
    {

        $friendships = $this->findFriendships(Status::ACCEPTED, $groupSlug)->get(['sender_id', 'recipient_id']);
        $recipients  = $friendships->pluck('recipient_id')->all();
        $senders     = $friendships->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }
    
    /**
     * Get the query builder of the 'friend' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getMutualFriendsQueryBuilder(Model $other)
    {
        $user1['friendships'] = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user1['recipients'] = $user1['friendships']->pluck('recipient_id')->all();
        $user1['senders'] = $user1['friendships']->pluck('sender_id')->all();
        
        $user2['friendships'] = $other->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user2['recipients'] = $user2['friendships']->pluck('recipient_id')->all();
        $user2['senders'] = $user2['friendships']->pluck('sender_id')->all();
        
        $mutualFriendships = array_unique(
                                    array_intersect(
                                        array_merge($user1['recipients'], $user1['senders']),
                                        array_merge($user2['recipients'], $user2['senders'])
                                    )
                                );

        return $this->whereNotIn('id', [$this->getKey(), $other->getKey()])->whereIn('id', $mutualFriendships);
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder($groupSlug = '')
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        $friendIds = array_unique(array_merge($recipients, $senders));


        $fofs = Friendship::where('status', Status::ACCEPTED)
                            ->where(function ($query) use ($friendIds) {
                                $query->where(function ($q) use ($friendIds) {
                                    $q->whereIn('sender_id', $friendIds);
                                })->orWhere(function ($q) use ($friendIds) {
                                    $q->whereIn('recipient_id', $friendIds);
                                });
                            })
                            ->whereGroup($this, $groupSlug)
                            ->get(['sender_id', 'recipient_id']);

        $fofIds = array_unique(
            array_merge($fofs->pluck('sender_id')->all(), $fofs->pluck('recipient_id')->all())
        );

//      Alternative way using collection helpers
//        $fofIds = array_unique(
//            $fofs->map(function ($item) {
//                return [$item->sender_id, $item->recipient_id];
//            })->flatten()->all()
//        );


        return $this->whereIn('id', $fofIds)->whereNotIn('id', $friendIds);
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        @trigger_error(sprintf('The ' . __METHOD__ . ' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->morphMany(Friendship::class, 'sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    protected function friendshipRequests()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function groups()
    {
        return $this->morphMany(FriendFriendshipGroups::class, 'friend');
    }
    
    protected function getOrPaginate($builder, $perPage)
    {
        if ($perPage == 0) {
            return $builder->get();
        }
        return $builder->paginate($perPage);
    }
}
