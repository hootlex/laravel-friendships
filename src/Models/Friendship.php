<?php

namespace Hootlex\Friendships\Models;

use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Friendship
 * @package Hootlex\Friendships\Models
 */
class Friendship extends Model
{

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('friendships.tables.fr_pivot');

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sender()
    {
        return $this->morphTo('sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function recipient()
    {
        return $this->morphTo('recipient');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function groups() {
        return $this->hasMany(FriendFriendshipGroups::class, 'friendship_id');
    }

    /**
     * @param Model $recipient
     * @return $this
     */
    public function fillRecipient($recipient)
    {
        return $this->fill([
            'recipient_id' => $recipient->getKey(),
            'recipient_type' => $recipient->getMorphClass()
        ]);
    }

    /**
     * @param $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRecipient($query, $model)
    {
        return $query->where('recipient_id', $model->getKey())
            ->where('recipient_type', $model->getMorphClass());
    }

    /**
     * @param $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSender($query, $model)
    {
        return $query->where('sender_id', $model->getKey())
            ->where('sender_type', $model->getMorphClass());
    }

    /**
     * @param $query
     * @param Model $model
     * @param string $group_slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereGroup($query, $model, $group_slug)
    {

        $groups_pvt_tbl   = config('friendships.tables.fr_groups_pivot');
        $friends_pvt_tbl  = config('friendships.tables.fr_pivot');
        $groups_available = config('friendships.groups', []);

        if ('' !== $group_slug && isset($groups_available[$group_slug])) {

            $group_id = $groups_available[$group_slug];

            $query->join($groups_pvt_tbl, function ($join) use ($groups_pvt_tbl, $friends_pvt_tbl, $group_id, $model) {
                $join->on($groups_pvt_tbl . '.friendship_id', '=', $friends_pvt_tbl . '.id')
                    ->where($groups_pvt_tbl . '.group_id', '=', $group_id)
                    ->where(function ($query) use ($groups_pvt_tbl, $friends_pvt_tbl, $model) {
                        $query->where($groups_pvt_tbl . '.friend_id', '!=', $model->getKey())
                            ->where($groups_pvt_tbl . '.friend_type', '=', $model->getMorphClass());
                    })
                    ->orWhere($groups_pvt_tbl . '.friend_type', '!=', $model->getMorphClass());
            });

        }

        return $query;

    }

    /**
     * @param $query
     * @param Model $sender
     * @param Model $recipient
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenModels($query, $sender, $recipient)
    {
        $query->where(function ($queryIn) use ($sender, $recipient){
            $queryIn->where(function ($q) use ($sender, $recipient) {
                $q->whereSender($sender)->whereRecipient($recipient);
            })->orWhere(function ($q) use ($sender, $recipient) {
                $q->whereSender($recipient)->whereRecipient($sender);
            });
        });
    }
}
