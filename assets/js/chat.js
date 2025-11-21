/**
 * Casters.fi - Chat Widget
 */

class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.currentConversation = null;
        this.conversations = [];
        this.messages = [];
        this.pollInterval = null;
        this.init();
    }

    init() {
        this.createWidget();
        this.bindEvents();
        this.loadConversations();
        this.startPolling();
    }

    createWidget() {
        // Create toggle button
        const toggle = document.createElement('button');
        toggle.className = 'chat-toggle';
        toggle.id = 'chatToggle';
        toggle.innerHTML = `
            <i class="fas fa-comments"></i>
            <span class="unread-badge" id="chatUnreadBadge" style="display: none;">0</span>
        `;
        document.body.appendChild(toggle);

        // Create widget
        const widget = document.createElement('div');
        widget.className = 'chat-widget';
        widget.id = 'chatWidget';
        widget.innerHTML = `
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="chat-header-text">
                        <h4>Messages</h4>
                        <span>Chat with brands & influencers</span>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button id="chatMinimize" title="Minimize">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>

            <div class="chat-content" id="chatContent">
                <!-- Conversations List View -->
                <div class="chat-list-view" id="chatListView">
                    <div class="chat-search">
                        <input type="text" id="chatSearch" placeholder="Search users...">
                    </div>
                    <div class="chat-tabs">
                        <button class="chat-tab active" data-tab="conversations">
                            Chats <span class="tab-badge" id="chatTabBadge" style="display: none;">0</span>
                        </button>
                        <button class="chat-tab" data-tab="users">New Chat</button>
                    </div>
                    <div class="chat-conversations" id="chatConversations">
                        <!-- Conversations will be loaded here -->
                    </div>
                    <div class="chat-users" id="chatUsers" style="display: none;">
                        <!-- Users will be loaded here -->
                    </div>
                </div>

                <!-- Messages View -->
                <div class="chat-messages-view" id="chatMessagesView">
                    <div class="chat-messages-header">
                        <button class="chat-back-btn" id="chatBack">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div class="chat-messages-user">
                            <h4 id="chatUserName">User Name</h4>
                            <span id="chatUserType">Influencer</span>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <!-- Messages will be loaded here -->
                    </div>
                    <div class="chat-input-container">
                        <div class="chat-input-wrapper">
                            <button class="chat-emoji-btn" id="chatEmojiBtn">
                                <i class="fas fa-smile"></i>
                            </button>
                            <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
                            <button class="chat-send-btn" id="chatSend">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="emoji-picker" id="emojiPicker" style="display: none;">
                            <div class="emoji-picker-header">
                                <span>Emojis</span>
                                <button class="emoji-picker-close" id="emojiPickerClose"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="emoji-picker-tabs">
                                <button class="emoji-tab active" data-category="smileys">😀</button>
                                <button class="emoji-tab" data-category="gestures">👍</button>
                                <button class="emoji-tab" data-category="hearts">❤️</button>
                                <button class="emoji-tab" data-category="animals">🐱</button>
                                <button class="emoji-tab" data-category="food">🍕</button>
                                <button class="emoji-tab" data-category="objects">⚽</button>
                            </div>
                            <div class="emoji-picker-content" id="emojiPickerContent">
                                <!-- Emojis will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(widget);
    }

    bindEvents() {
        // Toggle chat
        document.getElementById('chatToggle').addEventListener('click', () => {
            this.toggle();
        });

        // Minimize
        document.getElementById('chatMinimize').addEventListener('click', () => {
            this.close();
        });

        // Back button
        document.getElementById('chatBack').addEventListener('click', () => {
            this.showConversationsList();
        });

        // Tabs
        document.querySelectorAll('.chat-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.tab;
                document.querySelectorAll('.chat-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                if (tabName === 'conversations') {
                    document.getElementById('chatConversations').style.display = 'block';
                    document.getElementById('chatUsers').style.display = 'none';
                } else {
                    document.getElementById('chatConversations').style.display = 'none';
                    document.getElementById('chatUsers').style.display = 'block';
                    this.loadUsers();
                }
            });
        });

        // Search
        let searchTimeout;
        document.getElementById('chatSearch').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const activeTab = document.querySelector('.chat-tab.active').dataset.tab;
                if (activeTab === 'users') {
                    this.loadUsers(e.target.value);
                }
            }, 300);
        });

        // Send message
        document.getElementById('chatSend').addEventListener('click', () => {
            this.sendMessage();
        });

        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Emoji picker
        document.getElementById('chatEmojiBtn').addEventListener('click', () => {
            this.toggleEmojiPicker();
        });

        document.getElementById('emojiPickerClose').addEventListener('click', () => {
            this.hideEmojiPicker();
        });

        // Emoji tabs
        document.querySelectorAll('.emoji-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.emoji-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.loadEmojis(tab.dataset.category);
            });
        });

        // Load default emojis
        this.loadEmojis('smileys');

        // Close emoji picker when clicking outside
        document.addEventListener('click', (e) => {
            const picker = document.getElementById('emojiPicker');
            const btn = document.getElementById('chatEmojiBtn');
            if (!picker.contains(e.target) && !btn.contains(e.target)) {
                this.hideEmojiPicker();
            }
        });
    }

    toggleEmojiPicker() {
        const picker = document.getElementById('emojiPicker');
        if (picker.style.display === 'none') {
            picker.style.display = 'block';
        } else {
            picker.style.display = 'none';
        }
    }

    hideEmojiPicker() {
        document.getElementById('emojiPicker').style.display = 'none';
    }

    loadEmojis(category) {
        const emojis = {
            smileys: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐'],
            gestures: ['👍', '👎', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '👋', '🤚', '🖐️', '✋', '🖖', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '👀', '👁️', '👅', '👄'],
            hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '♥️', '💌', '💋', '🫶'],
            animals: ['🐱', '🐶', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🐦', '🐤', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜', '🦟', '🦗', '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈', '🐊'],
            food: ['🍕', '🍔', '🍟', '🌭', '🍿', '🧂', '🥓', '🥚', '🍳', '🧇', '🥞', '🧈', '🍞', '🥐', '🥨', '🥯', '🥖', '🫓', '🧀', '🥗', '🥙', '🥪', '🌮', '🌯', '🫔', '🥫', '🍝', '🍜', '🍲', '🍛', '🍣', '🍱', '🥟', '🦪', '🍤', '🍙', '🍚', '🍘', '🍥', '🥠', '🥮', '🍢', '🍡', '🍧', '🍨', '🍦', '🥧', '🧁', '🍰', '🎂', '🍮', '🍭', '🍬', '🍫', '🍿', '🍩', '🍪', '☕', '🍵', '🧃', '🥤', '🧋', '🍶', '🍺', '🍻', '🥂', '🍷', '🥃', '🍸', '🍹', '🧉', '🍾'],
            objects: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🎮', '🕹️', '🎲', '🧩', '♟️', '🎭', '🎨', '🎬', '🎤', '🎧', '🎼', '🎹', '🥁', '🪘', '🎷', '🎺', '🪗', '🎸', '🪕', '🎻']
        };

        const container = document.getElementById('emojiPickerContent');
        const emojiList = emojis[category] || emojis.smileys;

        container.innerHTML = emojiList.map(emoji =>
            `<button class="emoji-item" data-emoji="${emoji}">${emoji}</button>`
        ).join('');

        // Bind click events
        container.querySelectorAll('.emoji-item').forEach(item => {
            item.addEventListener('click', () => {
                this.insertEmoji(item.dataset.emoji);
            });
        });
    }

    insertEmoji(emoji) {
        const input = document.getElementById('chatInput');
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const text = input.value;

        input.value = text.substring(0, start) + emoji + text.substring(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + emoji.length;
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        document.getElementById('chatWidget').classList.add('open');
        document.getElementById('chatToggle').innerHTML = `
            <i class="fas fa-times"></i>
        `;
        this.isOpen = true;
        this.loadConversations();
    }

    close() {
        document.getElementById('chatWidget').classList.remove('open');
        document.getElementById('chatToggle').innerHTML = `
            <i class="fas fa-comments"></i>
            <span class="unread-badge" id="chatUnreadBadge" style="display: none;">0</span>
        `;
        this.isOpen = false;
        this.updateUnreadBadge();
    }

    async loadConversations() {
        try {
            const response = await fetch('/casters/api/chat.php?action=conversations');
            const data = await response.json();

            if (data.conversations) {
                this.conversations = data.conversations;
                this.renderConversations();
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    renderConversations() {
        const container = document.getElementById('chatConversations');

        if (this.conversations.length === 0) {
            container.innerHTML = `
                <div class="chat-empty">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet.<br>Start a new chat!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.conversations.map(conv => `
            <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''}"
                 data-user-id="${conv.other_user_id}">
                <div class="conversation-avatar">
                    <i class="fas fa-${conv.user_type === 'brand' ? 'building' : 'user'}"></i>
                </div>
                <div class="conversation-info">
                    <div class="conversation-name">
                        <span>${conv.first_name} ${conv.last_name}</span>
                        <span class="conversation-time">${this.formatTime(conv.last_message_time)}</span>
                    </div>
                    <div class="conversation-preview">${this.truncate(conv.last_message, 40)}</div>
                </div>
            </div>
        `).join('');

        // Bind click events
        container.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                this.openConversation(userId);
            });
        });

        // Update badge
        const totalUnread = this.conversations.reduce((sum, c) => sum + parseInt(c.unread_count), 0);
        this.updateTabBadge(totalUnread);
    }

    async loadUsers(search = '') {
        try {
            const response = await fetch(`/casters/api/chat.php?action=users&search=${encodeURIComponent(search)}`);
            const data = await response.json();

            const container = document.getElementById('chatUsers');

            if (!data.users || data.users.length === 0) {
                container.innerHTML = `
                    <div class="chat-empty">
                        <i class="fas fa-search"></i>
                        <p>No users found</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = data.users.map(user => `
                <div class="conversation-item" data-user-id="${user.id}">
                    <div class="conversation-avatar">
                        <i class="fas fa-${user.user_type === 'brand' ? 'building' : 'user'}"></i>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">
                            <span>${user.display_name}</span>
                        </div>
                        <div class="conversation-preview">${user.user_type === 'brand' ? 'Brand' : 'Influencer'}</div>
                    </div>
                </div>
            `).join('');

            // Bind click events
            container.querySelectorAll('.conversation-item').forEach(item => {
                item.addEventListener('click', () => {
                    const userId = item.dataset.userId;
                    this.openConversation(userId);
                });
            });
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    async openConversation(userId) {
        this.currentConversation = userId;

        try {
            const response = await fetch(`/casters/api/chat.php?action=messages&user_id=${userId}`);
            const data = await response.json();

            if (data.user) {
                document.getElementById('chatUserName').textContent =
                    `${data.user.first_name} ${data.user.last_name}`;
                document.getElementById('chatUserType').textContent =
                    data.user.user_type === 'brand' ? 'Brand' : 'Influencer';
            }

            this.messages = data.messages || [];
            this.renderMessages();
            this.showMessagesView();

            // Reload conversations to update unread count
            this.loadConversations();
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    renderMessages() {
        const container = document.getElementById('chatMessages');

        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="chat-empty">
                    <i class="fas fa-comment"></i>
                    <p>No messages yet.<br>Send the first message!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.messages.map(msg => `
            <div class="message ${msg.direction}" data-message-id="${msg.id}">
                <div class="message-bubble">
                    ${msg.message ? this.escapeHtml(msg.message) : ''}
                    ${msg.attachment ? this.renderAttachment(msg.attachment, msg.attachment_name) : ''}
                    ${msg.direction === 'sent' && !msg.attachment ? `<button class="message-edit-btn" onclick="chatWidget.startEdit(${msg.id})"><i class="fas fa-pencil-alt"></i></button>` : ''}
                </div>
                <div class="message-meta">
                    <span class="message-time">${this.formatTime(msg.created_at)}</span>
                    ${msg.is_edited == 1 ? '<span class="message-edited">edited</span>' : ''}
                    ${msg.direction === 'sent' ? `<span class="message-status">${msg.is_read == 1 ? '<i class="fas fa-check-double read"></i>' : '<i class="fas fa-check"></i>'}</span>` : ''}
                </div>
            </div>
        `).join('');

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    renderAttachment(url, name) {
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

    startEdit(messageId) {
        const message = this.messages.find(m => m.id == messageId);
        if (!message) return;

        this.editingMessageId = messageId;
        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('chatSend');

        input.value = message.message;
        input.focus();
        sendBtn.innerHTML = '<i class="fas fa-save"></i>';
        sendBtn.classList.add('editing');

        // Add cancel button
        if (!document.getElementById('chatCancelEdit')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.id = 'chatCancelEdit';
            cancelBtn.className = 'chat-cancel-btn';
            cancelBtn.innerHTML = '<i class="fas fa-times"></i>';
            cancelBtn.onclick = () => this.cancelEdit();
            sendBtn.parentNode.insertBefore(cancelBtn, sendBtn);
        }
    }

    cancelEdit() {
        this.editingMessageId = null;
        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('chatSend');
        const cancelBtn = document.getElementById('chatCancelEdit');

        input.value = '';
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        sendBtn.classList.remove('editing');

        if (cancelBtn) cancelBtn.remove();
    }

    async editMessage(messageId, newMessage) {
        try {
            const formData = new FormData();
            formData.append('message_id', messageId);
            formData.append('message', newMessage);

            const response = await fetch('/casters/api/chat.php?action=edit', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Update message in array
                const index = this.messages.findIndex(m => m.id == messageId);
                if (index !== -1) {
                    this.messages[index] = {
                        ...this.messages[index],
                        message: newMessage,
                        is_edited: 1
                    };
                }
                this.renderMessages();
                this.cancelEdit();
            } else {
                alert(data.error || 'Failed to edit message');
            }
        } catch (error) {
            console.error('Error editing message:', error);
        }
    }

    async sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();

        if (!message || !this.currentConversation) return;

        // Check if we're editing
        if (this.editingMessageId) {
            await this.editMessage(this.editingMessageId, message);
            return;
        }

        try {
            const formData = new FormData();
            formData.append('receiver_id', this.currentConversation);
            formData.append('message', message);

            const response = await fetch('/casters/api/chat.php?action=send', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Add message to UI
                this.messages.push({
                    ...data.message,
                    direction: 'sent'
                });
                this.renderMessages();
                input.value = '';
            } else {
                alert(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    showMessagesView() {
        document.getElementById('chatListView').style.display = 'none';
        document.getElementById('chatMessagesView').classList.add('active');
    }

    showConversationsList() {
        document.getElementById('chatListView').style.display = 'block';
        document.getElementById('chatMessagesView').classList.remove('active');
        this.currentConversation = null;
    }

    async updateUnreadBadge() {
        try {
            const response = await fetch('/casters/api/chat.php?action=unread_count');
            const data = await response.json();

            const badge = document.getElementById('chatUnreadBadge');
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        } catch (error) {
            console.error('Error getting unread count:', error);
        }
    }

    updateTabBadge(count) {
        const badge = document.getElementById('chatTabBadge');
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }

    startPolling() {
        // Poll for new messages every 5 seconds
        this.pollInterval = setInterval(() => {
            if (this.isOpen && this.currentConversation) {
                this.openConversation(this.currentConversation);
            } else {
                this.updateUnreadBadge();
            }
        }, 5000);

        // Initial badge update
        this.updateUnreadBadge();
    }

    formatTime(timestamp) {
        if (!timestamp) return '';

        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        // Less than 1 minute
        if (diff < 60000) return 'now';

        // Less than 1 hour
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm';

        // Less than 1 day
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h';

        // Less than 1 week
        if (diff < 604800000) return Math.floor(diff / 86400000) + 'd';

        // Otherwise show date
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    truncate(text, length) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat widget when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in (check for session indicator)
    if (document.body.classList.contains('logged-in') || document.querySelector('.dashboard-layout')) {
        window.chatWidget = new ChatWidget();
    }
});
