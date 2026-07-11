<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MentalCare AI - Compassionate Support Chat</title>
    <meta name="description" content="A calm, supportive AI chatbot for managing stress, anxiety, and overwhelmed feelings. Confidential, private, and always available.">
    <!-- Calm stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <main class="app-container">
        <!-- App Header (HCI Principle: User Control & Status) -->
        <header class="app-header">
            <div class="header-title-container">
                <h1>AI Support Chat</h1>
                <span class="status-sub">Online</span>
            </div>
            
            <!-- Clear Conversation button (HCI: User Control & Freedom) -->
            <button id="clearChatBtn" class="btn-clear-chat" title="Clear Conversation" aria-label="Clear Conversation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
            </button>
        </header>

        <!-- Message Feed Area -->
        <section id="chatArea" class="chat-area" aria-label="Chat messages history">
            <!-- Initial Welcome Message from the Bot -->
            <div class="message-wrapper bot">
                <div class="bot-avatar">
                    <!-- Heart & Leaf stylized robot icon -->
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2a10 10 0 0 0-10 10c0 5.523 4.477 10 10 10s10-4.477 10-10A10 10 0 0 0 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-3-9.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5S11.33 12 10.5 12s-1.5-.67-1.5-1.5zm6 0c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5S17.33 12 16.5 12s-1.5-.67-1.5-1.5zm-5.5 4.5c1.24 1.5 3.76 1.5 5 0 .28-.33.77-.38 1.1-.1.33.28.38.77.1 1.1-2.02 2.42-6.18 2.42-8.2 0-.28-.33-.23-.82.1-1.1.33-.28.82-.23 1.1.1z"/>
                    </svg>
                </div>
                <div class="message-bubble">
                    Hello! I'm your MentalCare assistant. How are you feeling today? I'm here to listen and provide support.
                </div>
            </div>

            <!-- Bot Bouncing Dots Thinking Indicator (HCI Principle: Visibility of System Status) -->
            <div id="typingIndicator" class="typing-indicator" aria-label="Bot is thinking">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </section>

        <!-- Permanent, Non-intrusive Disclaimer (Ethical / Medical Requirement) -->
        <footer class="disclaimer-container">
            <strong>Disclaimer:</strong> This chatbot is an AI assistant, not a replacement for professional mental health therapy, counseling, or clinical care. If you are experiencing an immediate crisis, please contact your local emergency services or a crisis helpline.
        </footer>

        <!-- Input Area (Form-less AJAX submission) -->
        <section id="inputContainer" class="input-container">
            <div class="input-wrapper">
                <input id="messageInput" type="text" class="message-input" placeholder="Type a message..." autocomplete="off">
                <button id="emojiBtn" class="btn-emoji" title="Feelings Check-in" aria-label="Check-in mood">
                    <!-- Smiley face icon -->
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </button>
            </div>
            
            <button id="sendBtn" class="btn-send" title="Send Message" aria-label="Send Message">
                <!-- Paper airplane send icon -->
                <svg viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </section>

        <!-- Bottom Navigation Bar (Visual Shell & Consistency) -->
        <nav class="bottom-nav" aria-label="Bottom Navigation">
            <a href="#" class="nav-item">
                <!-- Home icon -->
                <svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Home
            </a>
            <a href="#" class="nav-item">
                <!-- Progress/Graph icon -->
                <svg viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Progress
            </a>
            <a href="#" class="nav-item active">
                <!-- Chat bubble icon -->
                <svg viewBox="0 0 24 24">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Chat
            </a>
            <a href="#" class="nav-item">
                <!-- Calendar icon -->
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Appointments
            </a>
            <a href="#" class="nav-item">
                <!-- Profile/User icon -->
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Profile
            </a>
        </nav>

        <!-- Custom Confirmation Modal (HCI: User Control & Beautiful Aesthetics) -->
        <div id="confirmModal" class="modal-overlay" aria-hidden="true">
            <div class="modal-content">
                <h2>Clear Chat History?</h2>
                <p>This will permanently erase all our messages and start a fresh, quiet conversation. Are you sure you'd like to clear it?</p>
                <div class="modal-actions">
                    <button id="cancelClearBtn" class="btn-modal btn-cancel">Cancel</button>
                    <button id="confirmClearBtn" class="btn-modal btn-confirm">Clear Chat</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Main JavaScript controller -->
    <script src="assets/js/script.js"></script>
</body>
</html>
