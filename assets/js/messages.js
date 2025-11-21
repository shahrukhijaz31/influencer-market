/**
 * Shared Messages Functionality
 * Used across brand and influencer portals
 */

class MessagesManager {
    constructor(apiBase = '/casters/api/chat.php') {
        this.API_BASE = apiBase;
        this.currentUserId = null;
        this.conversations = [];
        this.messages = [];
        this.pollInterval = null;
        this.selectedFile = null;

        this.initializeElements();
        this.attachEventListeners();
    }

    initializeElements() {
        // Get all DOM elements
        this.elements = {
            conversationsList: document.getElementById('conversationsList'),
            usersList: document.getElementById('usersList'),
            chatMessages: document.getElementById('chatMessages'),
            chatContent: document.getElementById('chatContent'),
            emptyState: document.getElementById('emptyState'),
            messageForm: document.getElementById('messageForm'),
            messageInput: document.getElementById('messageInput'),
            sendBtn: document.getElementById('sendBtn'),
            chatUserName: document.getElementById('chatUserName'),
            chatUserType: document.getElementById('chatUserType'),
            chatAvatar: document.getElementById('chatAvatar'),
            searchInput: document.getElementById('searchInput'),
            emojiBtn: document.getElementById('emojiBtn'),
            emojiPicker: document.getElementById('emojiPicker'),
            attachBtn: document.getElementById('attachBtn'),
            attachmentInput: document.getElementById('attachmentInput'),
            attachmentPreview: document.getElementById('attachmentPreview'),
            removeAttachment: document.getElementById('removeAttachment'),
            backToList: document.getElementById('backToList'),
            newChatBtn: document.getElementById('newChatBtn'),
            unreadBadge: document.getElementById('unreadBadge'),
            conversationsSidebar: document.getElementById('conversationsSidebar'),
            chatArea: document.getElementById('chatArea')
        };
    }

    attachEventListeners() {
        // Message form submission
        this.elements.messageForm.addEventListener('submit', (e) => this.handleSendMessage(e));

        // Emoji picker
        this.elements.emojiBtn.addEventListener('click', (e) => this.toggleEmojiPicker(e));
        document.querySelectorAll('.emoji-item').forEach(item => {
            item.addEventListener('click', (e) => this.insertEmoji(e));
        });
        document.addEventListener('click', (e) => this.closeEmojiPicker(e));

        // Attachment handling
        this.elements.attachBtn.addEventListener('click', () => this.elements.attachmentInput.click());
        this.elements.attachmentInput.addEventListener('change', () => this.handleFileSelect());
        this.elements.removeAttachment.addEventListener('click', () => this.clearAttachment());

        // Tabs
        document.querySelectorAll('.conv-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.handleTabClick(e));
        });

        // Search
        let searchTimeout;
        this.elements.searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const activeTab = document.querySelector('.conv-tab.active').dataset.tab;
                if (activeTab === 'users') {
                    this.loadUsers(e.target.value);
                }
            }, 300);
        });

        // Back to list (mobile)
        this.elements.backToList.addEventListener('click', () => this.showConversationsList());

        // New chat button
        this.elements.newChatBtn.addEventListener('click', () => {
            document.querySelector('.conv-tab[data-tab="users"]').click();
        });

        // Auto-resize textarea
        this.elements.messageInput.addEventListener('input', (e) => {
            e.target.style.height = 'auto';
            e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
        });
    }

    // Initialize with selected user
    async init(selectedUserId = null) {
        await this.loadConversations();
        this.startPolling();

        if (selectedUserId) {
            this.openConversation(selectedUserId);
        }
    }

    // Load conversations
    async loadConversations() {
        try {
            const response = await fetch(`${this.API_BASE}?action=conversations`);
            const data = await response.json();

            if (data.conversations) {
                this.conversations = data.conversations;
                this.renderConversations();
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    // Render conversations
    renderConversations() {
        const container = this.elements.conversationsList;

        if (!this.conversations || this.conversations.length === 0) {
            container.innerHTML = `
                <div class="no-conversations">
                    <i class="fas fa-inbox"></i>
                    <p>No conversations yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.conversations.map(conv => `
            <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''} ${this.currentUserId == conv.id ? 'active' : ''}"
                 data-user-id="${conv.id}"
                 onclick="messagesManager.openConversation(${conv.id})">
                <div class="conversation-avatar">
                    ${conv.first_name.charAt(0).toUpperCase()}
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

        // Update unread badge
        const totalUnread = this.conversations.reduce((sum, c) => sum + parseInt(c.unread_count || 0), 0);
        const badge = this.elements.unreadBadge;
        if (totalUnread > 0) {
            badge.textContent = totalUnread;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }

    // Load users for new chat
    async loadUsers(search = '') {
        try {
            const response = await fetch(`${this.API_BASE}?action=users&search=${encodeURIComponent(search)}`);
            const data = await response.json();

            const container = this.elements.usersList;

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
                <div class="conversation-item" data-user-id="${user.id}" onclick="messagesManager.openConversation(${user.id})">
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

    // Open conversation
    async openConversation(userId) {
        this.currentUserId = userId;

        // Update URL without reload
        history.pushState({}, '', `?user=${userId}`);

        // Show chat area on mobile
        this.elements.conversationsSidebar.classList.add('hidden');
        this.elements.chatArea.classList.add('active');

        try {
            const response = await fetch(`${this.API_BASE}?action=messages&user_id=${userId}`);
            const data = await response.json();

            if (data.user) {
                this.elements.chatUserName.textContent = `${data.user.first_name} ${data.user.last_name}`;
                this.elements.chatUserType.textContent = data.user.user_type === 'brand' ? 'Brand' : 'Influencer';
                this.elements.chatAvatar.textContent = data.user.first_name.charAt(0).toUpperCase();
            }

            this.messages = data.messages || [];
            this.renderMessages();

            // Show chat content
            this.elements.emptyState.style.display = 'none';
            this.elements.chatContent.style.display = 'flex';

            // Update active state in list
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.userId == userId) {
                    item.classList.add('active');
                }
            });

            // Reload conversations to update unread count
            this.loadConversations();
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    // Render messages
    renderMessages() {
        const container = this.elements.chatMessages;

        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="chat-empty-state">
                    <i class="fas fa-comment"></i>
                    <h3>No messages yet</h3>
                    <p>Send the first message!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.messages.map(msg => `
            <div class="message ${msg.direction}">
                <div class="message-bubble">
                    ${msg.message ? this.escapeHtml(msg.message) : ''}
                    ${msg.attachment ? this.renderAttachment(msg.attachment, msg.attachment_name) : ''}
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

    // Render attachment
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

    // Send message
    async handleSendMessage(e) {
        e.preventDefault();

        const message = this.elements.messageInput.value.trim();

        if ((!message && !this.selectedFile) || !this.currentUserId) return;

        this.elements.sendBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('receiver_id', this.currentUserId);
            formData.append('message', message);

            if (this.selectedFile) {
                formData.append('attachment', this.selectedFile);
            }

            const response = await fetch(`${this.API_BASE}?action=send`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.messages.push({
                    ...data.message,
                    direction: 'sent'
                });
                this.renderMessages();
                this.elements.messageInput.value = '';
                this.elements.messageInput.style.height = 'auto';

                // Clear attachment
                this.clearAttachment();

                this.loadConversations();
            } else {
                alert(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
        } finally {
            this.elements.sendBtn.disabled = false;
        }
    }

    // Emoji picker methods
    toggleEmojiPicker(e) {
        e.stopPropagation();
        this.elements.emojiPicker.classList.toggle('active');
    }

    insertEmoji(e) {
        e.stopPropagation();
        const emoji = e.target.textContent;
        const input = this.elements.messageInput;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        input.value = input.value.substring(0, start) + emoji + input.value.substring(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + emoji.length;
        this.elements.emojiPicker.classList.remove('active');
    }

    closeEmojiPicker(e) {
        if (!this.elements.emojiBtn.contains(e.target) && !this.elements.emojiPicker.contains(e.target)) {
            this.elements.emojiPicker.classList.remove('active');
        }
    }

    // Attachment methods
    handleFileSelect() {
        if (this.elements.attachmentInput.files.length > 0) {
            this.selectedFile = this.elements.attachmentInput.files[0];
            document.getElementById('attachmentName').textContent = this.selectedFile.name;
            document.getElementById('attachmentSize').textContent = this.formatFileSize(this.selectedFile.size);
            this.elements.attachmentPreview.style.display = 'flex';
        }
    }

    clearAttachment() {
        this.selectedFile = null;
        this.elements.attachmentInput.value = '';
        this.elements.attachmentPreview.style.display = 'none';
    }

    // Tab handling
    handleTabClick(e) {
        document.querySelectorAll('.conv-tab').forEach(t => t.classList.remove('active'));
        e.currentTarget.classList.add('active');

        const tabName = e.currentTarget.dataset.tab;
        if (tabName === 'conversations') {
            this.elements.conversationsList.style.display = 'block';
            this.elements.usersList.classList.remove('active');
        } else {
            this.elements.conversationsList.style.display = 'none';
            this.elements.usersList.classList.add('active');
            this.loadUsers();
        }
    }

    // Show conversations list (mobile)
    showConversationsList() {
        this.elements.conversationsSidebar.classList.remove('hidden');
        this.elements.chatArea.classList.remove('active');
    }

    // Polling
    startPolling() {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => {
            this.loadConversations();
            if (this.currentUserId) {
                this.openConversation(this.currentUserId);
            }
        }, 5000);
    }

    // Utility functions
    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        return date.toLocaleDateString();
    }

    truncate(str, length) {
        if (!str) return '';
        return str.length > length ? str.substring(0, length) + '...' : str;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
}

// Global instance
let messagesManager;
