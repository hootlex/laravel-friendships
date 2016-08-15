<?php

namespace Hootlex\Friendships\Models;

use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Friendship
 * @package Hootlex\Friendships\Models
 *
 * @method \Illuminate\Database\Eloquent\Builder|Friendship pending()  Get pending friendships
 * @method \Illuminate\Database\Eloquent\Builder|Friendship accepted() Get accepted friendships
 * @method \Illuminate\Database\Eloquent\Builder|Friendship blocked()  Get blocked friendships
 * @method \Illuminate\Database\Eloquent\Builder|Friendship denied()   Get denied friendships
 */
class Friendship extends Model
{
    /**
     * @var string
     */
    public $table = 'friendships';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|Friendship
     */
    public function scopePending($query)
    {
        return $query->whereStatus(Status::PENDING);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Friendship
     */
    public function scopeAccepted($query)
    {
        return $query->whereStatus(Status::ACCEPTED);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|Friendship
     */
    public function scopeBlocked($query)
    {
        return $query->whereStatus(Status::BLOCKED);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|Friendship
     */
    public function scopeDenied($query)
    {
        return $query->whereStatus(Status::DENIED);
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
