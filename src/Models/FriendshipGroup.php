<?php

namespace Hootlex\Friendships\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FriendshipGroup
 * @package Hootlex\Friendships\Models
 */
class FriendshipGroup extends Model
{

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $fillable = ['slug', 'name'];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('friendships.tables.fr_groups');

        parent::__construct($attributes);
    }

    /**
     * @return mixed
     */
    public function grouped() {

        return $this->hasMany('Hootlex\Friendships\Models\FriendshipGrouped', 'group_id');

    }

}
