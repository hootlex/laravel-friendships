<?php

namespace Tests;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Mockery;

class FriendshipsEventsTest extends TestCase
{
    // use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        
        $this->sender    = createUser();
        $this->recipient = createUser();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function friend_request_is_sent()
    {
        Event::assertDispatched('friendships.sent');

        $this->sender->befriend($this->recipient);
    }

    /** @test */
    public function friend_request_is_accepted()
    {
        $this->sender->befriend($this->recipient);
        Event::assertDispatched('friendships.accepted');

        $this->recipient->acceptFriendRequest($this->sender);
    }

    /** @test */
    public function friend_request_is_denied()
    {
        $this->sender->befriend($this->recipient);
        Event::assertDispatched('friendships.denied');

        $this->recipient->denyFriendRequest($this->sender);
    }

    /** @test */
    public function friend_is_blocked()
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        Event::assertDispatched('friendships.blocked');

        $this->recipient->blockFriend($this->sender);
    }

    /** @test */
    public function friend_is_unblocked()
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        $this->recipient->blockFriend($this->sender);
        Event::assertDispatched('friendships.unblocked');

        $this->recipient->unblockFriend($this->sender);
    }

    /** @test */
    public function friendship_is_cancelled()
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        Event::assertDispatched('friendships.cancelled');

        $this->recipient->unfriend($this->sender);
    }
}
