<?php

namespace Hootlex\Friendships\Traits;

use Hootlex\Friendships\Models\Friendship;
use Hootlex\Friendships\Models\FriendFriendshipGroups;
use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

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

        return $friendship;

    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasFriendRequestFrom(Model $recipient)
    {
        return Friendship::whereRecipient($this)->whereSender($recipient)->whereStatus(Status::PENDING)->exists();
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
        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::DENIED,
        ]);
    }


    /**
     * @param Model $friend
     * @param $group_slug
     * @return bool
     */
    public function groupFriend(Model $friend, $group_slug)
    {

        $friendship       = $this->findFriendship($friend)->whereStatus(Status::ACCEPTED)->first();
        $groups_available = config('friendships.groups', []);

        if (!isset($groups_available[$group_slug]) || empty($friendship))
            return false;

        $group = $friendship->groups()->firstOrCreate([
            'friendship_id' => $friendship->id,
            'group_id'      => $groups_available[$group_slug],
            'friend_id'     => $friend->getKey(),
            'friend_type'   => $friend->getMorphClass(),
        ]);

        return $group->wasRecentlyCreated;

    }

    /**
     * @param Model $friend
     * @param $group_slug
     * @return bool
     */
    public function ungroupFriend(Model $friend, $group_slug = '')
    {

        $friendship       = $this->findFriendship($friend)->first();
        $groups_available = config('friendships.groups', []);

        if (empty($friendship))
            return false;

        $where = [
            'friendship_id' => $friendship->id,
            'friend_id'     => $friend->getKey(),
            'friend_type'   => $friend->getMorphClass(),
        ];

        if ('' !== $group_slug && isset($groups_available[$group_slug])) {
            $where['group_id'] = $groups_available[$group_slug];
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
        //if there is a friendship between two users delete it
        $this->findFriendship($recipient)->delete();

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);

        return $this->friends()->save($friendship);
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        return $this->findFriendship($recipient)->delete();
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
     * @param string $group_slug
     *
     */
    public function getAllFriendships($group_slug = '')
    {
        return $this->findFriendships(null, $group_slug)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $group_slug
     *
     */
    public function getPendingFriendships($group_slug = '')
    {
        return $this->findFriendships(Status::PENDING, $group_slug)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $group_slug
     *
     */
    public function getAcceptedFriendships($group_slug = '')
    {
        return $this->findFriendships(Status::ACCEPTED, $group_slug)->get();
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
     * @param string $group_slug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0, $group_slug = '')
    {
        if ($perPage == 0) {
            return $this->getFriendsQueryBuilder($group_slug)->get();
        } else {
            return $this->getFriendsQueryBuilder($group_slug)->paginate($perPage);
        }
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
        if ($perPage == 0) {
            return $this->getMutualFriendsQueryBuilder($other)->get();
        } else {
            return $this->getMutualFriendsQueryBuilder($other)->paginate($perPage);
        }
    }
    
    /**
     * Get the number of friends
     *
     * @return \Illuminate\Database\Eloquent\Collection
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
        if ($perPage == 0) {
            return $this->friendsOfFriendsQueryBuilder()->get();
        } else {
            return $this->friendsOfFriendsQueryBuilder()->paginate($perPage);
        }
    }


    /**
     * Get the number of friends
     *
     * @param string $group_slug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsCount($group_slug = '')
    {
        $friendsCount = $this->findFriendships(Status::ACCEPTED, $group_slug)->count();
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
        if($this->hasBlocked($recipient)){
            $this->unblockFriend($recipient);
            return true;
        }

        // if sender has a friendship with the recipient return false
        if ($friendship = $this->getFriendship($recipient)) {
            // if previous friendship was Denied then let the user send fr
            if($friendship->status != Status::DENIED){
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
     * @param string $group_slug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null, $group_slug = '')
    {

        $query = Friendship::where(function ($query) {
            $query->where(function ($q) {
                $q->whereSender($this);
            })->orWhere(function ($q) {
                $q->whereRecipient($this);
            });
        })->whereGroup($this, $group_slug);

        //if $status is passed, add where clause
        if(!is_null($status)){
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @param string $group_slug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder($group_slug = '')
    {
        $fr_fields        = ['sender_id', 'recipient_id'];

        $friendships = $this->findFriendships(Status::ACCEPTED, $group_slug)->get(['sender_id', 'recipient_id']);
        $recipients  = $friendships->lists('recipient_id')->all();
        $senders     = $friendships->lists('sender_id')->all();

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
        $user1['recipients'] = $user1['friendships']->lists('recipient_id')->all();
        $user1['senders'] = $user1['friendships']->lists('sender_id')->all();
        
        $user2['friendships'] = $other->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user2['recipients'] = $user2['friendships']->lists('recipient_id')->all();
        $user2['senders'] = $user2['friendships']->lists('sender_id')->all();
        
        $mutual_friendships = array_unique(
                                  array_intersect(
                                      array_merge($user1['recipients'], $user1['senders']),
                                      array_merge($user2['recipients'], $user2['senders'])
                                  )
                              );

        return $this->whereNotIn('id', [$this->getKey(), $other->getKey()])->whereIn('id', $mutual_friendships);
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     * @param string $group_slug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder($group_slug = '')
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->lists('recipient_id')->all();
        $senders = $friendships->lists('sender_id')->all();

        $friendIds = array_unique(array_merge($recipients, $senders));


        $fofs = Friendship::where('status', Status::ACCEPTED)
                          ->where(function ($query) use ($friendIds) {
                              $query->where(function ($q) use ($friendIds) {
                                  $q->whereIn('sender_id', $friendIds);
                              })->orWhere(function ($q) use ($friendIds) {
                                  $q->whereIn('recipient_id', $friendIds);
                              });
                          })
                          ->whereGroup($this, $group_slug)
                          ->get(['sender_id', 'recipient_id']);

        $fofIds = array_unique(
            array_merge($fofs->pluck('sender_id')->all(), $fofs->lists('recipient_id')->all())
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
    public function groups()
    {
        return $this->morphMany(FriendFriendshipGroups::class, 'friend');
    }

}
