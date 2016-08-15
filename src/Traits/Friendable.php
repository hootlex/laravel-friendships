<?php

namespace Hootlex\Friendships\Traits;

use Hootlex\Friendships\Direction;
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

        $this->friendshipRequests()->save($friendship);

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

        return $this->friendshipRequests()->save($friendship);
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
        return $this->findRequests()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getPendingFriendships()
    {
        return $this->findRequests(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedFriendships()
    {
        return $this->findRequests(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedFriendships()
    {
        return $this->findRequests(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFriendships()
    {
        return $this->findRequests(Status::BLOCKED)->get();
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
            return $this->friendships()->get();
        } else {
            return $this->friendships()->paginate($perPage);
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
        $friendsCount = $this->findRequests(Status::ACCEPTED)->count();
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
     * @param int $status
     * @param int $direction
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findRequests($status = null, $direction = Direction::ALL)
    {
        $query = Friendship::where(function ($query) use ($direction) {
            if (($direction & Direction::OUTGOING) == Direction::OUTGOING) {
                $query->where(function ($q) {
                    $q->whereSender($this);
                });
            }

            if (($direction & Direction::INCOMING) == Direction::INCOMING) {
                $query->orWhere(function ($q) {
                    $q->whereRecipient($this);
                });
            }
        });

        //if $status is passed, add where clause
        if(!is_null($status)){
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder()
    {
        $friendships = $this->findRequests(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
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
    protected function friendshipRequests()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }

    /**
     * Get friendships of this user.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function friendships()
    {
        $friendships = $this->findRequests(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->lists('recipient_id')->all();
        $senders = $friendships->lists('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

    /**
     * Get requests of this user.
     *
     * @param int $direction
     *
     * @return Friendship|\Illuminate\Database\Eloquent\Builder
     */
    public function requests($direction = Direction::ALL)
    {
        $friendships = $this->findRequests(null, $direction);

        return $friendships;
    }

    /**
     * @deprecated Will be removed in version 2
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        @trigger_error(sprintf('The '.__METHOD__.' method was deprecated in version 1.1 and will be removed in version 2.0. You should implement this method yourself in %s.', get_class($this)), E_USER_DEPRECATED);
        return $this->morphMany(Friendship::class, 'sender');
    }
}
