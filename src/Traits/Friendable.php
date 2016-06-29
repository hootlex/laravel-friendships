<?php

namespace Hootlex\Friendships\Traits;

use Hootlex\Friendships\Models\Friendship;
use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

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
     */
    public function getAllFriendships()
    {
        return $this->findFriendships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getPendingFriendships()
    {
        return $this->findFriendships(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedFriendships()
    {
        return $this->findFriendships(Status::ACCEPTED)->get();
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
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0)
    {
        if ($perPage == 0) {
            return $this->getFriendsQueryBuilder()->get();
        } else {
            return $this->getFriendsQueryBuilder()->paginate($perPage);
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsCount()
    {
        $friendsCount = $this->findFriendships(Status::ACCEPTED)->count();
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
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null)
    {
        $query = Friendship::where(function ($query) {
                $query->where(function ($q) {
                    $q->whereSender($this);
                })->orWhere(function ($q) {
                    $q->whereRecipient($this);
                });
            });

        //if $status is passed, add where clause
        if(!is_null($status)){
            $query->where('status', $status);
        }

        return $query;
    }


    /**
     * Get the query builder of the 'friend' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->lists('recipient_id')->all();
        $senders = $friendships->lists('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder()
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
                          })->get(['sender_id', 'recipient_id']);

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

}
