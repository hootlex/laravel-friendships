<?php

namespace Hootlex\Friendships\Traits;

use Hootlex\Friendships\Models\Friendship;
use Hootlex\Friendships\Models\FriendshipGrouped;
use Hootlex\Friendships\Models\FriendshipGroup;
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

        $this->friends()->save($friendship);
      
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
     * @param string $groupSlug
     * @return bool
     */
    public function groupFriend(Model $friend, $groupSlug)
    {

        $friendship = $this->findFriendship($friend)->whereStatus(Status::ACCEPTED)->first();
        $group      = FriendshipGroup::where('slug', $groupSlug)->first();

        if (empty($group)) return false;

        $grouped = $friendship->grouped()->firstOrCreate([
            'friendship_id' => $friendship->id,
            'group_id'      => $group->id,
            'friend_id'     => $friend->getKey(),
            'friend_type'   => $friend->getMorphClass(),
        ]);

        return $grouped->wasRecentlyCreated;

    }

    /**
     * @param Model $friend
     * @param string $groupSlug
     * @return bool
     */
    public function ungroupFriend(Model $friend, $groupSlug="")
    {

        $friendship       = $this->findFriendship($friend)->first();

        if (empty($friendship)) {
                    return false;
        }

        $where = [
            'friendship_id' => $friendship->id,
            'friend_id'     => $friend->getKey(),
            'friend_type'   => $friend->getMorphClass(),
        ];

        if (!empty($groupSlug)) {
            $group = FriendshipGroup::where('slug', $groupSlug)->first();
            if (!empty($group)) $where['group_id'] = $group->id;
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

        return $this->friends()->save($friendship);
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
     * @param Model $recipient
     *
     * @return \Hootlex\Friendships\Models\Friendship
     */
    public function getFriendship(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $group
     *
     */
    public function getAllFriendships($group = '')
    {
        return $this->findFriendships(null, $group)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $group
     *
     */
    public function getPendingFriendships($group = '')
    {
        return $this->findFriendships(Status::PENDING, $group)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $group
     *
     */
    public function getAcceptedFriendships($group = '')
    {
        return $this->findFriendships(Status::ACCEPTED, $group)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedFriendships()
    {
        return $this->findFriendships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFriendships()
    {
        return $this->findFriendships(Status::BLOCKED)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friends()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendRequests()
    {
        return Friendship::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param int $perPage Number
     * @param string $group
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0, $group = '')
    {
        return $this->getOrPaginate($this->getFriendsQueryBuilder($group), $perPage);
    }
    
    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMutualFriends(Model $other, $perPage = 0)
    {
        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other), $perPage);
    }
    
    /**
     * Get the number of friends
     *
     * @return integer
     */
    public function getMutualFriendsCount($other)
    {
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends($perPage = 0)
    {
        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder(), $perPage);
    }


    /**
     * Get the number of friends
     *
     * @param string $group
     *
     * @return integer
     */
    public function getFriendsCount($group = '')
    {
        $friendsCount = $this->findFriendships(Status::ACCEPTED, $group)->count();
        return $friendsCount;
    }


    /**
     * Get groups for Friend
     *
     * @param Model $model
     * @return array
     */
    public function getGroupsFor(Model $model) {

        $result     = [];
        $friendship = $this->getFriendship($model);

        if( !empty($friendship) ) {

            $grouped = $friendship->grouped()
                ->with('group')
                ->where('friend_id', $model->getKey())
                ->where('friend_type', $model->getMorphClass())
                ->get();

            if( false === $grouped->isEmpty() ) {

                foreach ($grouped as $item)
                    $result[] = $item->group->slug;

            }

        }

        return $result;

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
     * @param string $group
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null, $group = '')
    {

        $query = Friendship::where(function ($query) {
            $query->where(function ($q) {
                $q->whereSender($this);
            })->orWhere(function ($q) {
                $q->whereRecipient($this);
            });
        })->whereGroup($this, $group);

        //if $status is passed, add where clause
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @param string $group
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder($group = '')
    {

        $friendships = $this->findFriendships(Status::ACCEPTED, $group)->get(['sender_id', 'recipient_id']);
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
     * @param string $group
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder($group = '')
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
                            ->whereGroup($this, $group)
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function grouped()
    {
        return $this->morphMany(FriendshipGrouped::class, 'friend');
    }

    /**
     * @param $builder
     * @param $perPage
     * @return mixed
     */
    protected function getOrPaginate($builder, $perPage)
    {
        if ($perPage == 0) {
            return $builder->get();
        }
        return $builder->paginate($perPage);
    }

}
