<?php

namespace Hootlex\Friendships\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FriendshipGrouped
 * @package Hootlex\Friendships\Models
 */
class FriendshipGrouped extends Model
{

    /**
     * @var array
     */
    protected $fillable = ['friendship_id', 'group_id', 'friend_id', 'friend_type'];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('friendships.tables.fr_groups_pivot');

        parent::__construct($attributes);
    }

    /**
     * @return mixed
     */
    public function group() {

        return $this->belongsTo('Hootlex\Friendships\Models\FriendshipGroup', 'group_id');

    }

    /**
     * @return mixed
     */
    public function friendship() {

        return $this->belongsTo('Hootlex\Friendships\Models\Friendship', 'friendship_id');

    }

}
