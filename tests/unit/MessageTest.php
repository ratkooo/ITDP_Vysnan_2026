<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Message;

class MessageTest extends TestCase
{
    /**
     * Test that the Message constructor correctly assigns all properties.
     */
    public function testMessageConstructorSetsAllProperties(): void
    {
        $message = new Message(
            1,
            42,
            7,
            'senderUser',
            'Hello World',
            '2024-01-01 12:00:00'
        );

        $this->assertEquals(1, $message->id);
        $this->assertEquals(42, $message->userId);
        $this->assertEquals(7, $message->senderId);
        $this->assertEquals('senderUser', $message->senderUsername);
        $this->assertEquals('Hello World', $message->messageText);
        $this->assertEquals('2024-01-01 12:00:00', $message->createdAt);
    }

    /**
     * Test that nullable fields (id and createdAt) accept null.
     */
    public function testMessageAcceptsNullableFields(): void
    {
        $message = new Message(
            null,
            10,
            5,
            'anotherUser',
            'Test message content',
            null
        );

        $this->assertNull($message->id);
        $this->assertNull($message->createdAt);
        $this->assertEquals(10, $message->userId);
        $this->assertEquals(5, $message->senderId);
        $this->assertEquals('anotherUser', $message->senderUsername);
        $this->assertEquals('Test message content', $message->messageText);
    }

    /**
     * Test that message properties are directly mutable (not readonly).
     */
    public function testMessagePropertiesAreMutable(): void
    {
        $message = new Message(1, 1, 1, 'original', 'original text');

        $message->messageText = 'updated text';
        $message->senderUsername = 'updated sender';

        $this->assertEquals('updated text', $message->messageText);
        $this->assertEquals('updated sender', $message->senderUsername);
    }
}
