<?php

namespace Hootlex\Friendships\Models;

use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FriendFriendshipGroups
 * @package Hootlex\Friendships\Models
 */
class FriendFriendshipGroups extends Model
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

}
