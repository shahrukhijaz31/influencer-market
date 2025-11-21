<!-- Chat Widget -->
<div class="chat-widget" id="chatWidget">
    <button class="chat-toggle" id="chatToggle">
        <i class="fas fa-comments"></i>
        <span class="chat-badge" id="chatBadge" style="display: none;">0</span>
    </button>

    <div class="chat-panel" id="chatPanel">
        <div class="chat-panel-header">
            <h4>Messages</h4>
            <button class="chat-close" id="chatClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="chat-conversations" id="chatConversations">
            <div class="chat-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading conversations...</span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages" style="display: none;">
            <div class="chat-messages-header">
                <button class="chat-back" id="chatBack">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-recipient" id="chatRecipient">
                    <span class="chat-recipient-name"></span>
                </div>
            </div>

            <div class="chat-messages-body" id="chatMessagesBody">
                <!-- Messages will be loaded here -->
            </div>

            <div class="chat-input-area">
                <div class="emoji-picker-container">
                    <button type="button" class="emoji-toggle" id="emojiToggle">
                        <i class="fas fa-smile"></i>
                    </button>
                    <div class="emoji-picker" id="emojiPicker" style="display: none;">
                        <div class="emoji-list">
                            <span class="emoji" data-emoji="😀">😀</span>
                            <span class="emoji" data-emoji="😃">😃</span>
                            <span class="emoji" data-emoji="😄">😄</span>
                            <span class="emoji" data-emoji="😁">😁</span>
                            <span class="emoji" data-emoji="😅">😅</span>
                            <span class="emoji" data-emoji="😂">😂</span>
                            <span class="emoji" data-emoji="🤣">🤣</span>
                            <span class="emoji" data-emoji="😊">😊</span>
                            <span class="emoji" data-emoji="😇">😇</span>
                            <span class="emoji" data-emoji="🙂">🙂</span>
                            <span class="emoji" data-emoji="😉">😉</span>
                            <span class="emoji" data-emoji="😍">😍</span>
                            <span class="emoji" data-emoji="🥰">🥰</span>
                            <span class="emoji" data-emoji="😘">😘</span>
                            <span class="emoji" data-emoji="😋">😋</span>
                            <span class="emoji" data-emoji="😎">😎</span>
                            <span class="emoji" data-emoji="🤔">🤔</span>
                            <span class="emoji" data-emoji="🤗">🤗</span>
                            <span class="emoji" data-emoji="👍">👍</span>
                            <span class="emoji" data-emoji="👏">👏</span>
                            <span class="emoji" data-emoji="🙌">🙌</span>
                            <span class="emoji" data-emoji="🎉">🎉</span>
                            <span class="emoji" data-emoji="❤️">❤️</span>
                            <span class="emoji" data-emoji="💯">💯</span>
                        </div>
                    </div>
                </div>
                <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
                <button class="chat-send" id="chatSend">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>
