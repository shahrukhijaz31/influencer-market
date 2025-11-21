<?php
/**
 * Casters.fi - Influencer Messages Page
 * Uses the same API as the chat widget for consistency
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isInfluencer()) {
    redirect('login.html');
}

// Get selected user from URL
$selectedUserId = isset($_GET['user']) ? intval($_GET['user']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            height: calc(100vh - 140px);
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .conversations-sidebar {
            border-right: 1px solid #e5e5e5;
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversations-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111;
            margin: 0;
        }

        .new-chat-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .new-chat-btn:hover {
            transform: scale(1.05);
        }

        .conversations-search {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: #e879f9;
            box-shadow: 0 0 0 3px rgba(232, 121, 249, 0.1);
        }

        .search-input-wrapper i {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .conversations-tabs {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
        }

        .conv-tab {
            flex: 1;
            padding: 0.875rem;
            background: none;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }

        .conv-tab.active {
            color: #e879f9;
        }

        .conv-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        }

        .conv-tab .badge {
            background: #e879f9;
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
            font-size: 0.6875rem;
            margin-left: 0.375rem;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 1rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f5f5f5;
            text-decoration: none;
        }

        .conversation-item:hover {
            background: #fafafa;
        }

        .conversation-item.active {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border-left: 3px solid #e879f9;
        }

        .conversation-item.unread {
            background: rgba(232, 121, 249, 0.05);
        }

        .conversation-avatar {
            position: relative;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .unread-dot {
            position: absolute;
            top: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: #e879f9;
            border-radius: 50%;
            border: 2px solid white;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: 600;
            color: #111;
            margin-bottom: 0.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversation-name span:first-child {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: #999;
            flex-shrink: 0;
        }

        .conversation-preview {
            font-size: 0.8125rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-area {
            display: flex;
            flex-direction: column;
            background: #fafafa;
        }

        #chatContent {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-header {
            padding: 1rem 1.5rem;
            background: white;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-header-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .chat-header-info h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #111;
            margin: 0 0 0.125rem;
        }

        .chat-header-info p {
            font-size: 0.8125rem;
            color: #666;
            margin: 0;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 70%;
            display: flex;
            flex-direction: column;
        }

        .message.sent {
            align-self: flex-end;
        }

        .message.received {
            align-self: flex-start;
        }

        .message-bubble {
            padding: 0.875rem 1.125rem;
            border-radius: 16px;
            font-size: 0.9375rem;
            line-height: 1.5;
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.received .message-bubble {
            background: white;
            color: #111;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
            font-size: 0.6875rem;
            color: #999;
        }

        .message.sent .message-meta {
            justify-content: flex-end;
        }

        .message-edited {
            font-style: italic;
        }

        .message-status .read {
            color: #67e8f9;
        }

        .chat-input-area {
            padding: 1rem 1.5rem;
            background: white;
            border-top: 1px solid #e5e5e5;
        }

        .chat-input-form {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .chat-input-wrapper {
            flex: 1;
            position: relative;
        }

        .chat-input-wrapper textarea {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            font-size: 0.9375rem;
            resize: none;
            max-height: 120px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .chat-input-wrapper textarea:focus {
            outline: none;
            border-color: #e879f9;
            box-shadow: 0 0 0 3px rgba(232, 121, 249, 0.1);
        }

        .chat-input-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .chat-action-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f5f5f5;
            color: #666;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
            position: relative;
        }

        .chat-action-btn:hover {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            color: #e879f9;
        }

        .emoji-picker-container {
            position: absolute;
            bottom: 100%;
            left: 0;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 0.75rem;
            display: none;
            z-index: 100;
            width: 280px;
        }

        .emoji-picker-container.active {
            display: block;
        }

        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .emoji-item {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .emoji-item:hover {
            background: #f0f0f0;
            transform: scale(1.1);
        }

        .attachment-input {
            display: none;
        }

        .chat-send-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            transition: all 0.2s;
        }

        .chat-send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.4);
        }

        .chat-send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .attachment-preview {
            padding: 0.5rem 1rem;
            background: #f5f5f5;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: none;
            align-items: center;
            gap: 0.75rem;
        }

        .attachment-preview.active {
            display: flex;
        }

        .attachment-preview-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .attachment-preview-info {
            flex: 1;
            min-width: 0;
        }

        .attachment-preview-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #111;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attachment-preview-size {
            font-size: 0.75rem;
            color: #666;
        }

        .attachment-preview-remove {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .attachment-preview-remove:hover {
            color: #e879f9;
        }

        .message-attachment {
            margin-top: 0.5rem;
        }

        .message-image img {
            max-width: 250px;
            max-height: 200px;
            border-radius: 8px;
            cursor: pointer;
        }

        .message-file a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            color: inherit;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .message.received .message-file a {
            background: rgba(0,0,0,0.05);
        }

        .message-file a:hover {
            background: rgba(255,255,255,0.3);
        }

        .message.received .message-file a:hover {
            background: rgba(0,0,0,0.1);
        }

        .message-file i {
            font-size: 1.25rem;
        }

        .chat-empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .chat-empty-state i {
            font-size: 4rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .chat-empty-state h3 {
            font-size: 1.25rem;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .chat-empty-state p {
            color: #666;
        }

        .no-conversations {
            padding: 2rem;
            text-align: center;
            color: #666;
        }

        .no-conversations i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
            display: block;
        }

        .users-list {
            display: none;
        }

        .users-list.active {
            display: block;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
            }

            .conversations-sidebar.hidden {
                display: none;
            }

            .chat-area {
                display: none;
            }

            .chat-area.active {
                display: flex;
            }

            .back-to-list {
                display: flex !important;
            }
        }

        .back-to-list {
            display: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="dashboard-main">
            <?php include '../includes/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="messages-container">
                    <div class="conversations-sidebar" id="conversationsSidebar">
                        <div class="conversations-header">
                            <h2>Messages</h2>
                            <button class="new-chat-btn" id="newChatBtn" title="New Chat">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="conversations-search">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search..." id="searchInput">
                            </div>
                        </div>
                        <div class="conversations-tabs">
                            <button class="conv-tab active" data-tab="conversations">
                                Chats <span class="badge" id="unreadBadge" style="display: none;">0</span>
                            </button>
                            <button class="conv-tab" data-tab="users">New Chat</button>
                        </div>
                        <div class="conversations-list" id="conversationsList">
                            <div class="loading"><i class="fas fa-spinner"></i></div>
                        </div>
                        <div class="users-list" id="usersList"></div>
                    </div>

                    <div class="chat-area" id="chatArea">
                        <div class="chat-empty-state" id="emptyState">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the list to start messaging</p>
                        </div>

                        <div id="chatContent" style="display: none;">
                            <div class="chat-header">
                                <button class="back-to-list" id="backToList">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <div class="chat-header-avatar" id="chatAvatar">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="chat-header-info">
                                    <h3 id="chatUserName">Brand Name</h3>
                                    <p id="chatUserType">Brand</p>
                                </div>
                            </div>

                            <div class="chat-messages" id="chatMessages"></div>

                            <div class="chat-input-area">
                                <div class="attachment-preview" id="attachmentPreview" style="display: none;">
                                    <div class="attachment-preview-icon">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div class="attachment-preview-info">
                                        <div class="attachment-preview-name" id="attachmentName"></div>
                                        <div class="attachment-preview-size" id="attachmentSize"></div>
                                    </div>
                                    <button type="button" class="attachment-preview-remove" id="removeAttachment">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form class="chat-input-form" id="messageForm">
                                    <div class="chat-input-actions">
                                        <div style="position: relative;">
                                            <button type="button" class="chat-action-btn" id="emojiBtn" title="Add emoji">
                                                <i class="fas fa-smile"></i>
                                            </button>
                                            <div class="emoji-picker-container" id="emojiPicker">
                                                <div class="emoji-grid">
                                                    <span class="emoji-item">😀</span>
                                                    <span class="emoji-item">😃</span>
                                                    <span class="emoji-item">😄</span>
                                                    <span class="emoji-item">😁</span>
                                                    <span class="emoji-item">😅</span>
                                                    <span class="emoji-item">😂</span>
                                                    <span class="emoji-item">🤣</span>
                                                    <span class="emoji-item">😊</span>
                                                    <span class="emoji-item">😇</span>
                                                    <span class="emoji-item">🙂</span>
                                                    <span class="emoji-item">😉</span>
                                                    <span class="emoji-item">😍</span>
                                                    <span class="emoji-item">🥰</span>
                                                    <span class="emoji-item">😘</span>
                                                    <span class="emoji-item">😋</span>
                                                    <span class="emoji-item">😎</span>
                                                    <span class="emoji-item">🤩</span>
                                                    <span class="emoji-item">🥳</span>
                                                    <span class="emoji-item">😏</span>
                                                    <span class="emoji-item">😌</span>
                                                    <span class="emoji-item">🤔</span>
                                                    <span class="emoji-item">🤗</span>
                                                    <span class="emoji-item">🤭</span>
                                                    <span class="emoji-item">😐</span>
                                                    <span class="emoji-item">😑</span>
                                                    <span class="emoji-item">😶</span>
                                                    <span class="emoji-item">🙄</span>
                                                    <span class="emoji-item">😬</span>
                                                    <span class="emoji-item">👍</span>
                                                    <span class="emoji-item">👎</span>
                                                    <span class="emoji-item">👏</span>
                                                    <span class="emoji-item">🙌</span>
                                                    <span class="emoji-item">🤝</span>
                                                    <span class="emoji-item">💪</span>
                                                    <span class="emoji-item">❤️</span>
                                                    <span class="emoji-item">🧡</span>
                                                    <span class="emoji-item">💛</span>
                                                    <span class="emoji-item">💚</span>
                                                    <span class="emoji-item">💙</span>
                                                    <span class="emoji-item">💜</span>
                                                    <span class="emoji-item">🖤</span>
                                                    <span class="emoji-item">🔥</span>
                                                    <span class="emoji-item">✨</span>
                                                    <span class="emoji-item">💯</span>
                                                    <span class="emoji-item">💰</span>
                                                    <span class="emoji-item">📱</span>
                                                    <span class="emoji-item">💻</span>
                                                    <span class="emoji-item">📸</span>
                                                    <span class="emoji-item">🎬</span>
                                                    <span class="emoji-item">🎯</span>
                                                    <span class="emoji-item">🚀</span>
                                                    <span class="emoji-item">⭐</span>
                                                    <span class="emoji-item">🌟</span>
                                                    <span class="emoji-item">💫</span>
                                                    <span class="emoji-item">✅</span>
                                                    <span class="emoji-item">❌</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="chat-action-btn" id="attachBtn" title="Attach file">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <input type="file" class="attachment-input" id="attachmentInput" accept="image/*,.pdf,.doc,.docx,.txt">
                                    </div>
                                    <div class="chat-input-wrapper">
                                        <textarea name="message" placeholder="Type a message..." rows="1" id="messageInput"></textarea>
                                    </div>
                                    <button type="submit" class="chat-send-btn" id="sendBtn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        const API_BASE = '/casters/api/chat.php';
        let currentUserId = <?php echo $selectedUserId ? $selectedUserId : 'null'; ?>;
        let conversations = [];
        let messages = [];
        let pollInterval = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadConversations();
            startPolling();

            if (currentUserId) {
                openConversation(currentUserId);
            }
        });

        async function loadConversations() {
            try {
                const response = await fetch(`${API_BASE}?action=conversations`);
                const data = await response.json();

                if (data.conversations) {
                    conversations = data.conversations;
                    renderConversations();
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        function renderConversations() {
            const container = document.getElementById('conversationsList');

            if (conversations.length === 0) {
                container.innerHTML = `
                    <div class="no-conversations">
                        <i class="fas fa-comments"></i>
                        <p>No conversations yet</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = conversations.map(conv => `
                <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''} ${currentUserId == conv.other_user_id ? 'active' : ''}"
                     data-user-id="${conv.other_user_id}" onclick="openConversation(${conv.other_user_id})">
                    <div class="conversation-avatar">
                        ${conv.first_name.charAt(0).toUpperCase()}
                        ${conv.unread_count > 0 ? '<span class="unread-dot"></span>' : ''}
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">
                            <span>${conv.first_name} ${conv.last_name}</span>
                            <span class="conversation-time">${formatTime(conv.last_message_time)}</span>
                        </div>
                        <div class="conversation-preview">${truncate(conv.last_message, 40)}</div>
                    </div>
                </div>
            `).join('');

            const totalUnread = conversations.reduce((sum, c) => sum + parseInt(c.unread_count || 0), 0);
            const badge = document.getElementById('unreadBadge');
            if (totalUnread > 0) {
                badge.textContent = totalUnread;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }

        async function loadUsers(search = '') {
            try {
                const response = await fetch(`${API_BASE}?action=users&search=${encodeURIComponent(search)}`);
                const data = await response.json();

                const container = document.getElementById('usersList');

                if (!data.users || data.users.length === 0) {
                    container.innerHTML = `
                        <div class="no-conversations">
                            <i class="fas fa-search"></i>
                            <p>No users found</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = data.users.map(user => `
                    <div class="conversation-item" data-user-id="${user.id}" onclick="openConversation(${user.id})">
                        <div class="conversation-avatar">
                            ${user.display_name.charAt(0).toUpperCase()}
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <span>${user.display_name}</span>
                            </div>
                            <div class="conversation-preview">${user.user_type === 'brand' ? 'Brand' : 'Influencer'}</div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        async function openConversation(userId) {
            currentUserId = userId;
            history.pushState({}, '', `?user=${userId}`);

            document.getElementById('conversationsSidebar').classList.add('hidden');
            document.getElementById('chatArea').classList.add('active');

            try {
                const response = await fetch(`${API_BASE}?action=messages&user_id=${userId}`);
                const data = await response.json();

                if (data.user) {
                    document.getElementById('chatUserName').textContent =
                        `${data.user.first_name} ${data.user.last_name}`;
                    document.getElementById('chatUserType').textContent =
                        data.user.user_type === 'brand' ? 'Brand' : 'Influencer';
                    document.getElementById('chatAvatar').textContent =
                        data.user.first_name.charAt(0).toUpperCase();
                }

                messages = data.messages || [];
                renderMessages();

                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('chatContent').style.display = 'flex';

                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.dataset.userId == userId) {
                        item.classList.add('active');
                    }
                });

                loadConversations();
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function renderMessages() {
            const container = document.getElementById('chatMessages');

            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="chat-empty-state">
                        <i class="fas fa-comment"></i>
                        <h3>No messages yet</h3>
                        <p>Send the first message!</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.direction}">
                    <div class="message-bubble">
                        ${msg.message ? escapeHtml(msg.message) : ''}
                        ${msg.attachment ? renderAttachment(msg.attachment, msg.attachment_name) : ''}
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${formatTime(msg.created_at)}</span>
                        ${msg.is_edited == 1 ? '<span class="message-edited">edited</span>' : ''}
                        ${msg.direction === 'sent' ? `<span class="message-status">${msg.is_read == 1 ? '<i class="fas fa-check-double read"></i>' : '<i class="fas fa-check"></i>'}</span>` : ''}
                    </div>
                </div>
            `).join('');

            container.scrollTop = container.scrollHeight;
        }

        function renderAttachment(url, name) {
            const extension = url.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);

            if (isImage) {
                return `<div class="message-attachment message-image">
                    <a href="${url}" target="_blank"><img src="${url}" alt="${name || 'Image'}"></a>
                </div>`;
            } else {
                const icon = extension === 'pdf' ? 'fa-file-pdf' :
                            ['doc', 'docx'].includes(extension) ? 'fa-file-word' : 'fa-file';
                return `<div class="message-attachment message-file">
                    <a href="${url}" target="_blank" download>
                        <i class="fas ${icon}"></i>
                        <span>${name || 'Download file'}</span>
                    </a>
                </div>`;
            }
        }

        document.getElementById('messageForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if ((!message && !selectedFile) || !currentUserId) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('receiver_id', currentUserId);
                formData.append('message', message);

                // Add attachment if selected
                if (selectedFile) {
                    formData.append('attachment', selectedFile);
                }

                const response = await fetch(`${API_BASE}?action=send`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    messages.push({
                        ...data.message,
                        direction: 'sent'
                    });
                    renderMessages();
                    input.value = '';
                    input.style.height = 'auto';

                    // Clear attachment
                    selectedFile = null;
                    attachmentInput.value = '';
                    attachmentPreview.style.display = 'none';

                    loadConversations();
                } else {
                    alert(data.error || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
            } finally {
                sendBtn.disabled = false;
            }
        });

        document.querySelectorAll('.conv-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.conv-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const tabName = this.dataset.tab;
                if (tabName === 'conversations') {
                    document.getElementById('conversationsList').style.display = 'block';
                    document.getElementById('usersList').classList.remove('active');
                } else {
                    document.getElementById('conversationsList').style.display = 'none';
                    document.getElementById('usersList').classList.add('active');
                    loadUsers();
                }
            });
        });

        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const activeTab = document.querySelector('.conv-tab.active').dataset.tab;
                if (activeTab === 'users') {
                    loadUsers(this.value);
                }
            }, 300);
        });

        document.getElementById('backToList').addEventListener('click', function() {
            document.getElementById('conversationsSidebar').classList.remove('hidden');
            document.getElementById('chatArea').classList.remove('active');
        });

        document.getElementById('newChatBtn').addEventListener('click', function() {
            document.querySelector('.conv-tab[data-tab="users"]').click();
        });

        document.getElementById('messageInput').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Emoji picker
        const emojiBtn = document.getElementById('emojiBtn');
        const emojiPicker = document.getElementById('emojiPicker');

        emojiBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            emojiPicker.classList.toggle('active');
        });

        document.querySelectorAll('.emoji-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                const emoji = this.textContent;
                const input = document.getElementById('messageInput');
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = input.value.substring(0, start) + emoji + input.value.substring(end);
                input.focus();
                input.selectionStart = input.selectionEnd = start + emoji.length;
                emojiPicker.classList.remove('active');
            });
        });

        document.addEventListener('click', function(e) {
            if (!emojiBtn.contains(e.target)) {
                emojiPicker.classList.remove('active');
            }
        });

        // Attachment handling
        const attachBtn = document.getElementById('attachBtn');
        const attachmentInput = document.getElementById('attachmentInput');
        const attachmentPreview = document.getElementById('attachmentPreview');
        let selectedFile = null;

        attachBtn.addEventListener('click', function() {
            attachmentInput.click();
        });

        attachmentInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                selectedFile = this.files[0];
                document.getElementById('attachmentName').textContent = selectedFile.name;
                document.getElementById('attachmentSize').textContent = formatFileSize(selectedFile.size);
                attachmentPreview.style.display = 'flex';
            }
        });

        document.getElementById('removeAttachment').addEventListener('click', function() {
            selectedFile = null;
            attachmentInput.value = '';
            attachmentPreview.style.display = 'none';
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function startPolling() {
            pollInterval = setInterval(() => {
                if (currentUserId) {
                    refreshMessages();
                }
                loadConversations();
            }, 5000);
        }

        async function refreshMessages() {
            try {
                const response = await fetch(`${API_BASE}?action=messages&user_id=${currentUserId}`);
                const data = await response.json();

                if (data.messages && data.messages.length !== messages.length) {
                    messages = data.messages;
                    renderMessages();
                }
            } catch (error) {
                console.error('Error refreshing messages:', error);
            }
        }

        function formatTime(timestamp) {
            if (!timestamp) return '';

            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'now';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'h';
            if (diff < 604800000) return Math.floor(diff / 86400000) + 'd';

            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function truncate(text, length) {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
