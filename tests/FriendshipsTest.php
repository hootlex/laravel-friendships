<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FriendshipsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function user_can_send_a_friend_request()
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }

    /** @test */
    public function user_can_not_send_a_friend_request_if_frienship_is_pending()
    {
        $sender = createUser();
        $recipient = createUser();
        $sender->befriend($recipient);
        $sender->befriend($recipient);
        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }


    /** @test */
    public function user_can_send_a_friend_request_if_frienship_is_denied()
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $recipient->denyFriendRequest($sender);

        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }

    /** @test */
    public function user_is_friend_with_another_user_if_accepts_a_friend_request()
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);
        //accept fr
        $recipient->acceptFriendRequest($sender);

        $this->assertTrue($recipient->isFriendWith($sender));
        $this->assertTrue($sender->isFriendWith($recipient));
        //fr has been delete
        $this->assertCount(0, $recipient->getFriendRequests());
    }

    /** @test */
    public function user_is_not_friend_with_another_user_until_he_accepts_a_friend_request()
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);

        $this->assertFalse($recipient->isFriendWith($sender));
        $this->assertFalse($sender->isFriendWith($recipient));
    }

    /** @test */
    public function user_has_friend_request_from_another_user_if_he_received_a_friend_request()
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);

        $this->assertTrue($recipient->hasFriendRequestFrom($sender));
        $this->assertFalse($sender->hasFriendRequestFrom($recipient));
    }

    /** @test */
    public function user_has_not_friend_request_from_another_user_if_he_accepted_the_friend_request()
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);
        //accept fr
        $recipient->acceptFriendRequest($sender);

        $this->assertFalse($recipient->hasFriendRequestFrom($sender));
        $this->assertFalse($sender->hasFriendRequestFrom($recipient));
    }

    /** @test */
    public function user_cannot_accept_his_own_friend_request(){
        $sender = createUser();
        $recipient = createUser();

        //send fr
        $sender->befriend($recipient);

        $sender->acceptFriendRequest($recipient);
        $this->assertFalse($recipient->isFriendWith($sender));
    }

    /** @test */
    public function user_can_deny_a_friend_request()
    {
        $sender = createUser();
        $recipient = createUser();
        $sender->befriend($recipient);

        $recipient->denyFriendRequest($sender);

        $this->assertFalse($recipient->isFriendWith($sender));

        //fr has been delete
        $this->assertCount(0, $recipient->getFriendRequests());
        $this->assertCount(1, $sender->getDeniedFriendships());
    }

    /** @test */
    public function user_can_block_another_user(){
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);

        $this->assertTrue($recipient->isBlockedBy($sender));
        $this->assertTrue($sender->hasBlocked($recipient));
        //sender is not blocked by receipient
        $this->assertFalse($sender->isBlockedBy($recipient));
        $this->assertFalse($recipient->hasBlocked($sender));
    }

    /** @test */
    public function user_can_unblock_a_blocked_user(){
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);
        $sender->unblockFriend($recipient);

        $this->assertFalse($recipient->isBlockedBy($sender));
        $this->assertFalse($sender->hasBlocked($recipient));
    }

    /** @test */
    public function user_can_send_friend_request_to_user_who_is_blocked(){
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);
        $sender->befriend($recipient);
        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }

    /** @test */
    public function it_returns_all_user_friendships(){
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(3, $sender->getAllFriendships());
    }

    /** @test */
    public function it_returns_accepted_user_friendships_number(){
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertEquals(2, $sender->getFriendsCount());
    }

    /** @test */
    public function it_returns_accepted_user_friendships(){
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(2, $sender->getAcceptedFriendships());
    }

    /** @test */
    public function it_returns_only_accepted_user_friendships(){
        $sender = createUser();
        $recipients = createUser([], 4);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(2, $sender->getAcceptedFriendships());

        $this->assertCount(1, $recipients[0]->getAcceptedFriendships());
        $this->assertCount(1, $recipients[1]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[2]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[3]->getAcceptedFriendships());
    }

    /** @test */
    public function it_returns_pending_user_friendships(){
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $this->assertCount(2, $sender->getPendingFriendships());
    }

    /** @test */
    public function it_returns_denied_user_friendships(){
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(1, $sender->getDeniedFriendships());
    }

    /** @test */
    public function it_returns_blocked_user_friendships(){
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->blockFriend($sender);
        $this->assertCount(1, $sender->getBlockedFriendships());
    }

    /** @test */
    public function it_returns_user_friends(){
        $sender = createUser();
        $recipients = createUser([], 4);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[3]->getFriends());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends());
    }

    /** @test */
    public function it_returns_user_friends_per_page(){
        $sender = createUser();
        $recipients = createUser([], 6);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $recipients[3]->acceptFriendRequest($sender);
        $recipients[4]->acceptFriendRequest($sender);


        $this->assertCount(2, $sender->getFriends(2));
        $this->assertCount(4, $sender->getFriends(0));
        $this->assertCount(4, $sender->getFriends(10));
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[5]->getFriends(2));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends());
    }

    /** @test */
    public function it_returns_user_friends_of_friends(){
        $sender = createUser();
        $recipients = createUser([], 2);
        $fofs = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->befriend($fof);
                $fof->acceptFriendRequest($recipient);
            }
        }

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(4, $recipients[0]->getFriends());
        $this->assertCount(3, $recipients[1]->getFriends());

        $this->assertCount(5, $sender->getFriendsOfFriends());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriendsOfFriends());
    }

    /** @test */
    public function it_returns_all_requests(){
        $sender = createUser();
        $recipients = createUser([], 4);

        $this->assertCount(0, $sender->requests()->get());

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->denyFriendRequest($sender);

        $this->assertCount(4, $sender->requests()->get());
        $this->assertCount(1, $recipients[1]->requests()->get());

        $this->containsOnlyInstancesOf(\Hootlex\Friendships\Models\Friendship::class, $sender->requests());
        $this->containsOnlyInstancesOf(\Hootlex\Friendships\Models\Friendship::class, $recipients[1]->requests()->get());

        // For each request, we want the sender and recipient (Both user objects)
        foreach ($sender->requests()->get() as $request) {
            $this->assertInstanceOf(\App\User::class, $request->sender);
            $this->assertInstanceOf(\App\User::class, $request->recipient);
        }
    }

    /** @test */
    public function it_returns_accepted_requests(){
        $sender = createUser();
        $recipients = createUser([], 3);
        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }
        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);

        $this->assertCount(2, $sender->requests()->accepted()->get());
        $this->assertCount(1, $recipients[1]->requests()->accepted()->get());
        $this->assertCount(0, $recipients[2]->requests()->accepted()->get());
    }

    /** @test */
    public function it_returns_pending_requests(){
        $sender = createUser();
        $recipients = createUser([], 6);

        $sender->befriend($recipients[0]);
        $sender->befriend($recipients[1]);
        $sender->befriend($recipients[2]);
        $sender->befriend($recipients[3]);
        // sender: 4 pendings
        $this->assertCount(4, $sender->requests()->pending()->get());


        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        // sender: 4 - 3 = 1 pending
        $this->assertCount(1, $sender->requests()->pending()->get());


        $recipients[3]->befriend($sender); // already pending
        $recipients[4]->befriend($sender);
        $recipients[5]->befriend($sender);
        // sender: 1 + 2 = 3 pendings
        $this->assertCount(3, $sender->requests()->pending()->get());



        $sender->acceptFriendRequest($recipients[4]);
        // sender: 3 - 1 = 2 pendings

        $this->assertCount(2, $sender->requests()->pending()->get());
        $this->assertCount(0, $recipients[1]->requests()->pending()->get());
        $this->assertCount(1, $recipients[3]->requests()->pending()->get());
    }

    /** @test */
    public function it_returns_denied_requests(){
        $sender = createUser();
        $recipients = createUser([], 3);
        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }
        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(1, $sender->requests()->denied()->get());
    }

    /** @test */
    public function it_returns_blocked_requests(){
        $sender = createUser();
        $recipients = createUser([], 3);
        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }
        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->blockFriend($sender);
        $this->assertCount(1, $sender->requests()->blocked()->get());
    }

    /** @test */
    public function it_returns_incoming_requests(){
        $sender = createUser();
        $recipients = createUser([], 7);

        $sender->befriend($recipients[0]);
        $sender->befriend($recipients[1]);
        $sender->befriend($recipients[2]);
        $sender->befriend($recipients[3]);

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $recipients[3]->befriend($sender); // /!\ Sender firstly request to be friend
        $recipients[5]->befriend($sender); // sender: 1
        $recipients[6]->befriend($sender); // sender: 2
        $sender->acceptFriendRequest($recipients[6]);


        $this->assertCount(2, $sender->requests(\Hootlex\Friendships\Direction::INCOMING)->get());
        $this->assertCount(1, $recipients[3]->requests(\Hootlex\Friendships\Direction::INCOMING)->get());
        $this->assertCount(1, $recipients[0]->requests(\Hootlex\Friendships\Direction::INCOMING)->get());

        // Incoming + accepted/denied/pending
        $this->assertCount(1, $sender->requests(\Hootlex\Friendships\Direction::INCOMING)->accepted()->get());
        $this->assertCount(1, $sender->requests(\Hootlex\Friendships\Direction::INCOMING)->pending()->get());
        $this->assertCount(0, $sender->requests(\Hootlex\Friendships\Direction::INCOMING)->denied()->get());
    }

    /** @test */
    public function it_returns_outgoing_requests(){
        $sender = createUser();
        $recipients = createUser([], 7);

        $sender->befriend($recipients[0]); // sender: 1
        $sender->befriend($recipients[1]); // sender: 2
        $sender->befriend($recipients[2]); // sender: 3
        $sender->befriend($recipients[3]); // sender: 4

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $recipients[3]->befriend($sender); // /!\ Sender firstly request to be friend
        $recipients[5]->befriend($sender);
        $recipients[6]->befriend($sender);
        $sender->acceptFriendRequest($recipients[6]);


        $this->assertCount(4, $sender->requests(\Hootlex\Friendships\Direction::OUTGOING)->get());
        $this->assertCount(0, $recipients[3]->requests(\Hootlex\Friendships\Direction::OUTGOING)->get());
        $this->assertCount(0, $recipients[0]->requests(\Hootlex\Friendships\Direction::OUTGOING)->get());
        $this->assertCount(1, $recipients[5]->requests(\Hootlex\Friendships\Direction::OUTGOING)->get());

        // Outgoing + accepted/denied/pending
        $this->assertCount(2, $sender->requests(\Hootlex\Friendships\Direction::OUTGOING)->accepted()->get());
        $this->assertCount(1, $sender->requests(\Hootlex\Friendships\Direction::OUTGOING)->pending()->get());
        $this->assertCount(1, $sender->requests(\Hootlex\Friendships\Direction::OUTGOING)->denied()->get());
    }
}
