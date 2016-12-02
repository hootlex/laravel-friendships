<?php

namespace Hootlex\Friendships\Models;

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
    public function grouped() {
        return $this->hasMany(FriendshipGrouped::class, 'friendship_id');
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
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereGroup($query, $model, $group)
    {

        $groupUserPivotTable = config('friendships.tables.fr_groups_pivot');
        $groupsTable         = config('friendships.tables.fr_groups');
        $friendsPivotTable   = config('friendships.tables.fr_pivot');


        if (!empty($group)) {

            $query->join($groupUserPivotTable, $groupUserPivotTable . '.friendship_id', '=', $friendsPivotTable . '.id')
                ->join($groupsTable, $groupsTable . '.id', '=', $groupUserPivotTable . '.group_id')
                ->where($groupsTable . '.slug', '=', $group)
                ->where(function ($query) use ($groupUserPivotTable, $friendsPivotTable, $model) {
                    $query->where($groupUserPivotTable . '.friend_id', '!=', $model->getKey())
                        ->where($groupUserPivotTable . '.friend_type', '=', $model->getMorphClass());
                })
                ->orWhere($groupUserPivotTable . '.friend_type', '!=', $model->getMorphClass());
            
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
