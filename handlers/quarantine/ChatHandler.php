<?php
/**
 * HotelOS - Chat Handler
 * 
 * Owner-Staff chat system with file sharing
 * Uses polling for message updates (server-light, no WebSocket)
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class ChatHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get user's conversations
     */
    public function getConversations(int $userId): array
    {
        return $this->db->query(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM chat_messages m 
                     WHERE m.conversation_id = c.id 
                     AND m.is_read = FALSE 
                     AND m.sender_id != :user_id) as unread_count,
                    (SELECT m.message FROM chat_messages m 
                     WHERE m.conversation_id = c.id 
                     ORDER BY m.created_at DESC LIMIT 1) as last_message
             FROM chat_conversations c
             WHERE JSON_CONTAINS(c.participants, :user_json)
             AND c.is_active = TRUE
             ORDER BY c.last_message_at DESC",
            ['user_id' => $userId, 'user_json' => json_encode($userId)]
        );
    }
    
    /**
     * Create or get conversation between two users
     */
    public function getOrCreateConversation(int $userId1, int $userId2, string $type = 'owner_staff'): int
    {
        // Check existing
        $existing = $this->db->queryOne(
            "SELECT id FROM chat_conversations 
             WHERE type = :type
             AND JSON_CONTAINS(participants, :u1)
             AND JSON_CONTAINS(participants, :u2)",
            ['type' => $type, 'u1' => json_encode($userId1), 'u2' => json_encode($userId2)]
        );
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Create new
        return $this->db->insert('chat_conversations', [
            'type' => $type,
            'participants' => json_encode([$userId1, $userId2])
        ]);
    }
    
    /**
     * Get messages in a conversation
     */
    public function getMessages(int $conversationId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT m.*, 
                    CASE WHEN m.sender_type = 'user' THEN 
                        (SELECT CONCAT(first_name, ' ', COALESCE(last_name, '')) FROM users WHERE id = m.sender_id)
                    WHEN m.sender_type = 'ai' THEN 'AI Assistant'
                    ELSE 'System'
                    END as sender_name
             FROM chat_messages m
             WHERE m.conversation_id = :conv_id
             ORDER BY m.created_at DESC
             LIMIT :limit OFFSET :offset",
            ['conv_id' => $conversationId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    /**
     * Send a message
     */
    public function sendMessage(int $conversationId, int $senderId, string $message, array $attachments = []): int
    {
        $messageId = $this->db->insert('chat_messages', [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'sender_type' => 'user',
            'message' => $message,
            'attachments' => !empty($attachments) ? json_encode($attachments) : null
        ]);
        
        // Update conversation
        $this->db->execute(
            "UPDATE chat_conversations SET last_message_at = NOW() WHERE id = :id",
            ['id' => $conversationId]
        );
        
        return $messageId;
    }
    
    /**
     * Send AI message
     */
    public function sendAIMessage(int $conversationId, string $message): int
    {
        $messageId = $this->db->insert('chat_messages', [
            'conversation_id' => $conversationId,
            'sender_id' => null,
            'sender_type' => 'ai',
            'message' => $message
        ]);
        
        $this->db->execute(
            "UPDATE chat_conversations SET last_message_at = NOW() WHERE id = :id",
            ['id' => $conversationId]
        );
        
        return $messageId;
    }
    
    /**
     * Mark messages as read
     */
    public function markAsRead(int $conversationId, int $userId): void
    {
        $this->db->execute(
            "UPDATE chat_messages 
             SET is_read = TRUE, read_at = NOW()
             WHERE conversation_id = :conv_id 
             AND sender_id != :user_id 
             AND is_read = FALSE",
            ['conv_id' => $conversationId, 'user_id' => $userId]
        );
    }
    
    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as cnt
             FROM chat_messages m
             JOIN chat_conversations c ON m.conversation_id = c.id
             WHERE JSON_CONTAINS(c.participants, :user_json)
             AND m.sender_id != :user_id
             AND m.is_read = FALSE",
            ['user_json' => json_encode($userId), 'user_id' => $userId]
        );
        
        return (int)($result['cnt'] ?? 0);
    }
    
    /**
     * Get new messages since timestamp (for polling)
     */
    public function getNewMessages(int $conversationId, string $since): array
    {
        return $this->db->query(
            "SELECT m.*, 
                    CASE WHEN m.sender_type = 'user' THEN 
                        (SELECT CONCAT(first_name, ' ', COALESCE(last_name, '')) FROM users WHERE id = m.sender_id)
                    WHEN m.sender_type = 'ai' THEN 'AI Assistant'
                    ELSE 'System'
                    END as sender_name
             FROM chat_messages m
             WHERE m.conversation_id = :conv_id
             AND m.created_at > :since
             ORDER BY m.created_at ASC",
            ['conv_id' => $conversationId, 'since' => $since]
        );
    }
    
    /**
     * Start AI conversation
     */
    public function startAIConversation(int $userId): int
    {
        $convId = $this->db->insert('chat_conversations', [
            'type' => 'ai_assist',
            'title' => 'AI Assistant',
            'participants' => json_encode([$userId])
        ]);
        
        // Send welcome message
        $this->sendAIMessage($convId, 
            "👋 Hello! I'm your AI Assistant.\n\n" .
            "I can help you with:\n" .
            "• Rate optimization suggestions\n" .
            "• Occupancy insights\n" .
            "• OTA management tips\n" .
            "• Housekeeping scheduling\n" .
            "• Business growth strategies\n\n" .
            "What would you like to know?"
        );
        
        return $convId;
    }
    
    /**
     * Get available staff for chat
     */
    public function getAvailableStaff(int $excludeUserId): array
    {
        return $this->db->query(
            "SELECT id, first_name, last_name, role, email
             FROM users
             WHERE id != :exclude
             AND is_active = 1
             ORDER BY first_name",
            ['exclude' => $excludeUserId]
        );
    }
}
