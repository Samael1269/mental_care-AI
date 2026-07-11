/**
 * script.js
 * Frontend controller for the MentalCare AI Chatbot.
 * Manages UI rendering, system status states, localStorage session storage, and backend fetches.
 */

document.addEventListener('DOMContentLoaded', () => {
    // DOM Element Selections
    const chatArea = document.getElementById('chatArea');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const clearChatBtn = document.getElementById('clearChatBtn');
    const typingIndicator = document.getElementById('typingIndicator');
    const emojiBtn = document.getElementById('emojiBtn');
    const inputContainer = document.getElementById('inputContainer');
    
    // Custom confirmation modal selections
    const confirmModal = document.getElementById('confirmModal');
    const confirmClearBtn = document.getElementById('confirmClearBtn');
    const cancelClearBtn = document.getElementById('cancelClearBtn');
    
    // Constant key for LocalStorage
    const STORAGE_KEY = 'mentalcare_conversation_history';
    
    // Default welcome message configuration
    const DEFAULT_WELCOME = {
        role: 'assistant',
        content: "Hello! I'm your MentalCare assistant. How are you feeling today? I'm here to listen and provide support."
    };

    // Main conversation state
    let conversationHistory = [];

    // SVG templates used to ensure no dependency-related visual layout breakages
    const botAvatarSVG = `
        <svg viewBox="0 0 24 24">
            <path d="M12 2a10 10 0 0 0-10 10c0 5.523 4.477 10 10 10s10-4.477 10-10A10 10 0 0 0 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-3-9.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5S11.33 12 10.5 12s-1.5-.67-1.5-1.5zm6 0c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5S17.33 12 16.5 12s-1.5-.67-1.5-1.5zm-5.5 4.5c1.24 1.5 3.76 1.5 5 0 .28-.33.77-.38 1.1-.1.33.28.38.77.1 1.1-2.02 2.42-6.18 2.42-8.2 0-.28-.33-.23-.82.1-1.1.33-.28.82-.23 1.1.1z"/>
        </svg>
    `;

    /**
     * Initializes the chat UI and starts a fresh conversation loop on reload/refresh
     */
    function init() {
        // Generate a new, unique session ID for server logging
        window.chatSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Start fresh: do not load previous history on refresh/reload
        conversationHistory = [DEFAULT_WELCOME];
        
        // Render current history to screen
        renderHistory();
        createMoodDrawer();
    }

    /**
     * Renders all historical messages to the UI (excluding system messages)
     */
    function renderHistory() {
        // Clear all except the typing indicator
        const messages = chatArea.querySelectorAll('.message-wrapper');
        messages.forEach(msg => msg.remove());

        // Remove any previous in-chat mood containers
        const moodContainers = chatArea.querySelectorAll('.inchat-mood-container');
        moodContainers.forEach(container => container.remove());

        // Append saved messages
        conversationHistory.forEach(msg => {
            if (msg.role !== 'system') {
                appendMessageToUI(msg.role, msg.content);
            }
        });

        // 1. Initial State: If conversation only has the default welcome message (HCI Visual-first flow)
        if (conversationHistory.length === 1) {
            // Hide input container
            inputContainer.classList.remove('active');
            
            // Build and render emoji mood grid inside chat
            renderMoodSelectionGrid();
        } else {
            // Reveal active text input container
            inputContainer.classList.add('active');
        }
        
        scrollToBottom();
    }

    /**
     * Builds and appends the in-chat visual mood options grid (reduces initial dialogue friction)
     */
    function renderMoodSelectionGrid() {
        const container = document.createElement('div');
        container.className = 'inchat-mood-container';
        container.id = 'inchatMoodContainer';
        container.innerHTML = `
            <div class="inchat-mood-title">Identify your mood to begin:</div>
            <div class="inchat-mood-grid">
                <button class="inchat-mood-card anxious" data-mood="Overwhelmed / Anxious" data-emoji="😰">
                    <span class="mood-emoji">😰</span>
                    <span class="mood-label">Overwhelmed</span>
                </button>
                <button class="inchat-mood-card sad" data-mood="Sad / Lonely" data-emoji="😔">
                    <span class="mood-emoji">😔</span>
                    <span class="mood-label">Sad</span>
                </button>
                <button class="inchat-mood-card stressed" data-mood="Stressed" data-emoji="😫">
                    <span class="mood-emoji">😫</span>
                    <span class="mood-label">Stressed</span>
                </button>
                <button class="inchat-mood-card tired" data-mood="Exhausted / Tired" data-emoji="😴">
                    <span class="mood-emoji">😴</span>
                    <span class="mood-label">Tired</span>
                </button>
                <button class="inchat-mood-card calm" data-mood="Calm / Peaceful" data-emoji="🌱">
                    <span class="mood-emoji">🌱</span>
                    <span class="mood-label">Calm</span>
                </button>
            </div>
        `;
        
        // Bind click events to mood cards
        container.querySelectorAll('.inchat-mood-card').forEach(card => {
            card.addEventListener('click', () => {
                const mood = card.getAttribute('data-mood');
                const emoji = card.getAttribute('data-emoji');
                
                // Store mood selection in state
                window.lastSelectedMood = mood;
                
                // Transition UI: Trigger message sending instantly using the visual selected emoji
                sendMessage(`${emoji} I'm feeling ${mood} right now.`);
            });
        });

        // Insert before typing indicator
        chatArea.insertBefore(container, typingIndicator);
    }

    /**
     * Dynamic helper to inject HTML templates for chatbot bubbles safely
     */
    function appendMessageToUI(role, content) {
        const wrapper = document.createElement('div');
        wrapper.className = `message-wrapper ${role === 'user' ? 'user' : 'bot'}`;

        // Create avatar for bot
        if (role === 'assistant' || role === 'bot') {
            const avatar = document.createElement('div');
            avatar.className = 'bot-avatar';
            avatar.innerHTML = botAvatarSVG;
            wrapper.appendChild(avatar);
        }

        // Create message bubble
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        
        // We use textContent to prevent script execution (XSS Prevention)
        // Check if there is specific markup we injected (like crisis banners) and handle it
        if (content.includes('Crisis Helpline') || content.includes('emergency services')) {
            // If the content is safe HTML formatted by our backend API (which has already escaped users' text), 
            // we can render it. However, to ensure maximum safety, we decode normal characters but wrap hotlines in styled components.
            bubble.innerHTML = formatCrisisText(content);
        } else {
            bubble.textContent = decodeHTMLEntities(content);
        }

        wrapper.appendChild(bubble);
        
        // Insert before typing indicator
        chatArea.insertBefore(wrapper, typingIndicator);
        scrollToBottom();
    }

    /**
     * Utility to decode basic HTML entities from sanitization for clean user presentation
     */
    function decodeHTMLEntities(text) {
        const txt = document.createElement('textarea');
        txt.innerHTML = text;
        return txt.value;
    }

    /**
     * Formats crisis keywords and helpline phone numbers dynamically to present supportive callouts
     */
    function formatCrisisText(text) {
        // First sanitize to text to prevent HTML injection
        let escapedText = decodeHTMLEntities(text);
        
        // Look for 988 or 741741 in text and style them
        const hotlineRegex = /(988|741741)/g;
        if (hotlineRegex.test(escapedText)) {
            return escapedText.replace(
                /(988)/g, 
                `<span class="crisis-banner"><strong>988</strong> (Call/Text 24/7 Crisis & Suicide Lifeline)</span>`
            ).replace(
                /(741741)/g,
                `<span class="crisis-banner"><strong>741741</strong> (Text HOME to connect with Crisis Text Line)</span>`
            );
        }
        return escapedText;
    }

    /**
     * Helper to scroll the conversation area smoothly to the latest message
     */
    function scrollToBottom() {
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    /**
     * Shows/hides the typing animation (HCI Principle: Visibility of System Status)
     */
    function toggleTypingIndicator(show) {
        if (show) {
            typingIndicator.style.display = 'flex';
            scrollToBottom();
        } else {
            typingIndicator.style.display = 'none';
        }
    }

    /**
     * Sends user message to the API relay handler and manages response cycles
     */
    async function sendMessage(overrideText = null) {
        let text = "";
        if (overrideText !== null) {
            text = overrideText;
        } else {
            text = messageInput.value.trim();
            if (!text) return;
            // Reset input field and refocus
            messageInput.value = '';
            messageInput.focus();
        }

        // 1. Push user message to history
        conversationHistory.push({ role: 'user', content: text });

        // Trigger UI layout transition (removes visual grid and reveals text input)
        renderHistory();

        // Close mood drawer if open
        const drawer = document.getElementById('moodDrawer');
        if (drawer) drawer.classList.remove('open');

        // 2. Show thinking animation (HCI principle)
        toggleTypingIndicator(true);

        try {
            // Prepare stateful payload containing the full conversation history (for memory retention) and session ID
            const payload = {
                session_id: window.chatSessionId,
                messages: conversationHistory
            };

            // Call PHP api relay
            const response = await fetch('api_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            // Toggle indicator off
            toggleTypingIndicator(false);

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(errData.message || 'Server error');
            }

            const data = await response.json();

            if (data.success && data.reply) {
                // 3. Append bot message to UI and history
                appendMessageToUI('assistant', data.reply);
                conversationHistory.push({ role: 'assistant', content: data.reply });
            } else {
                throw new Error(data.message || 'Malformed backend response');
            }

        } catch (error) {
            toggleTypingIndicator(false);
            console.error("Chat communication failure: ", error);
            
            // HCI Principle: Error Prevention & Compassionate Recovery
            // Print a soothing message to the user rather than raw stack codes
            appendMessageToUI(
                'assistant', 
                "I want to support you, but my connection is feeling a bit heavy right now. Please take a gentle, slow breath, and try sending your message again. I am here for you."
            );
        }
    }

    /**
     * Adds emotional check-in drawer above input container for better accessibility
     */
    function createMoodDrawer() {
        // Create drawer HTML element
        const moodDrawer = document.createElement('div');
        moodDrawer.id = 'moodDrawer';
        moodDrawer.className = 'mood-drawer';
        
        // Add styling dynamically to styles sheet via style tag for modular simplicity
        const style = document.createElement('style');
        style.textContent = `
            .mood-drawer {
                position: absolute;
                bottom: 85px;
                left: 16px;
                right: 16px;
                background-color: #ffffff;
                border-radius: 16px;
                box-shadow: 0 -4px 20px rgba(78, 110, 93, 0.12), 0 4px 20px rgba(0,0,0,0.05);
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                transform: translateY(120%);
                transition: transform 0.3s cubic-bezier(0.1, 0.8, 0.3, 1);
                z-index: 5;
                border: 1px solid var(--color-border);
            }
            .mood-drawer.open {
                transform: translateY(0);
            }
            .mood-drawer-title {
                font-size: 0.85rem;
                font-weight: 600;
                color: var(--color-sage-primary);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .mood-chips-container {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .mood-chip {
                background-color: var(--color-bg-main);
                border: 1px solid var(--color-border);
                padding: 8px 12px;
                border-radius: 20px;
                font-size: 0.88rem;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .mood-chip:hover {
                background-color: var(--color-sage-light);
                border-color: var(--color-sage-primary);
                color: var(--color-sage-primary);
                transform: translateY(-1px);
            }
            .mood-chip:active {
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);

        // Inject mood chips
        moodDrawer.innerHTML = `
            <div class="mood-drawer-title">How are you feeling right now?</div>
            <div class="mood-chips-container">
                <button class="mood-chip" data-mood="anxious">😰 Overwhelmed / Anxious</button>
                <button class="mood-chip" data-mood="sad">😔 Sad / Lonely</button>
                <button class="mood-chip" data-mood="stressed">😫 Stressed</button>
                <button class="mood-chip" data-mood="tired">😴 Exhausted / Tired</button>
                <button class="mood-chip" data-mood="calm">🌱 Calm / Peaceful</button>
            </div>
        `;

        // Insert inside container above input container
        const container = document.querySelector('.app-container');
        container.insertBefore(moodDrawer, document.querySelector('.disclaimer-container'));

        // Handle emoji button click (toggle drawer)
        emojiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            moodDrawer.classList.toggle('open');
        });

        // Close drawer when clicking outside
        document.addEventListener('click', (e) => {
            if (!moodDrawer.contains(e.target) && e.target !== emojiBtn) {
                moodDrawer.classList.remove('open');
            }
        });

        // Handle chip click
        moodDrawer.querySelectorAll('.mood-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const moodText = chip.innerText;
                const emotion = moodText.split(' ').slice(1).join(' '); // Extract e.g., "Overwhelmed / Anxious"
                window.lastSelectedMood = emotion;
                messageInput.value = `I am feeling ${emotion} right now.`;
                moodDrawer.classList.remove('open');
                messageInput.focus();
            });
        });
    }

    // --- EVENT LISTENERS ---

    // Send on button click
    sendBtn.addEventListener('click', sendMessage);

    // Send on Enter key press
    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    // Clear Conversation history (HCI: User Control & Freedom)
    clearChatBtn.addEventListener('click', () => {
        confirmModal.classList.add('open');
        confirmModal.setAttribute('aria-hidden', 'false');
    });

    // Cancel modal dismissal
    cancelClearBtn.addEventListener('click', () => {
        confirmModal.classList.remove('open');
        confirmModal.setAttribute('aria-hidden', 'true');
    });

    // Confirm chat reset action
    confirmClearBtn.addEventListener('click', () => {
        // Reset conversation history, generate a new session ID, and clear mood state
        conversationHistory = [DEFAULT_WELCOME];
        window.chatSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        window.lastSelectedMood = "Neutral / Exploring";
        renderHistory();
        confirmModal.classList.remove('open');
        confirmModal.setAttribute('aria-hidden', 'true');
    });

    // Close modal when clicking backdrop
    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            confirmModal.classList.remove('open');
            confirmModal.setAttribute('aria-hidden', 'true');
        }
    });

    // Run Initialization
    init();
});
