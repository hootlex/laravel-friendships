<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/*
 * Test User Personal Friend Groups
*/
class FriendshipsGroupsTest extends TestCase
{
    use DatabaseTransactions;
    
    
    /** @test */
    public function user_can_add_a_friend_to_a_group()
    {
        
        $sender    = createUser();
        $recipient = createUser();
        
        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);
        
        $this->assertTrue((boolean)$recipient->groupFriend($sender, 'acquaintances'));
        $this->assertTrue((boolean)$sender->groupFriend($recipient, 'family'));
        
        // it only adds a friend to a group once
        $this->assertFalse((boolean)$sender->groupFriend($recipient, 'family'));
        
        // expect that users have been attached to specified groups
        $this->assertCount(1, $sender->getFriends(0, 'family'));
        $this->assertCount(1, $recipient->getFriends(0, 'acquaintances'));
    
        $this->assertEquals($recipient->id, $sender->getFriends(0, 'family')->first()->id);
        $this->assertEquals($sender->id, $recipient->getFriends(0, 'acquaintances')->first()->id);
        
    }
    
    /** @test */
    public function user_cannot_add_a_non_friend_to_a_group()
    {
        $sender   = createUser();
        $stranger = createUser();
        
        $this->assertFalse((boolean)$sender->groupFriend($stranger, 'family'));
        $this->assertCount(0, $sender->getFriends(0, 'family'));
    }
    
    /** @test */
    public function user_can_remove_a_friend_from_group()
    {
        $sender    = createUser();
        $recipient = createUser();
        
        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);
    
        $recipient->groupFriend($sender, 'acquaintances');
        $recipient->groupFriend($sender, 'family');
        
        $this->assertEquals(1, $recipient->ungroupFriend($sender, 'acquaintances'));
        
        $this->assertCount(0, $sender->getFriends(0, 'acquaintances'));
        
        // expect that friend has been removed from acquaintances but not family
        $this->assertCount(0, $recipient->getFriends(0, 'acquaintances'));
        $this->assertCount(1, $recipient->getFriends(0, 'family'));
    }
    
    /** @test */
    public function user_can_remove_a_friend_from_all_groups()
    {
        $sender    = createUser();
        $recipient = createUser();
        
        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);
        
        $sender->groupFriend($recipient, 'family');
        $sender->groupFriend($recipient, 'acquaintances');
    
        $sender->ungroupFriend($recipient);
        
        $this->assertCount(0, $sender->getFriends(0, 'family'));
        $this->assertCount(0, $sender->getFriends(0, 'acquaintances'));
    }
    
    /** @test */
    public function it_returns_friends_of_a_group()
    {
        $sender     = createUser();
        $recipients = createUser([], 10);
        
        foreach ($recipients as $key => $recipient) {
            
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);
            
            if ($key % 2 === 0) {
                $sender->groupFriend($recipient, 'family');
            }
            
        }
        
        $this->assertCount(5, $sender->getFriends(0, 'family'));
        $this->assertCount(10, $sender->getFriends());
    }
    
    
}