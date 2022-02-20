<?php

namespace Tests;

use Mockery;
use Illuminate\Support\Facades\Event;
use Cuitcode\Friendships\Events\FriendshipsSent;
use Cuitcode\Friendships\Events\FriendshipsDenied;
use Cuitcode\Friendships\Events\FriendshipsBlocked;
use Cuitcode\Friendships\Events\FriendshipsAccepted;
use Cuitcode\Friendships\Events\FriendshipsCancelled;
use Cuitcode\Friendships\Events\FriendshipsUnblocked;

class FriendshipsEventsTest extends TestBase
{
    public function setUp() : void
    {
        parent::setUp();
        Event::fake();

        $this->sender = createUser();
        $this->recipient = createUser();
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    /** @test */
    public function friend_request_is_sent() : void
    {
        $this->sender->befriend($this->recipient);
        Event::assertDispatched(FriendshipsSent::class);
    }

    /** @test */
    public function friend_request_is_accepted() : void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        Event::assertDispatched(FriendshipsAccepted::class);
    }

    /** @test */
    public function friend_request_is_denied() : void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->denyFriendRequest($this->sender);
        Event::assertDispatched(FriendshipsDenied::class);
    }

    /** @test */
    public function friend_is_blocked() : void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        $this->recipient->blockFriend($this->sender);
        Event::assertDispatched(FriendshipsBlocked::class);
    }

    /** @test */
    public function friend_is_unblocked() : void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        $this->recipient->blockFriend($this->sender);
        $this->recipient->unblockFriend($this->sender);
        Event::assertDispatched(FriendshipsUnblocked::class);
    }

    /** @test */
    public function friendship_is_cancelled() : void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        $this->recipient->unfriend($this->sender);
        Event::assertDispatched(FriendshipsCancelled::class);
    }
}
