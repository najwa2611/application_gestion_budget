document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const textarea = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    const messageContainer = document.getElementById('messageContainer');
    
    // Initialize welcome message
    showWelcomeMessage();

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    textarea.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Auto-resize textarea
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    async function sendMessage() {
        const message = textarea.value.trim();
        if (!message) return;
        
        // Add user message to chat
        addMessage(message, 'user');
        textarea.value = '';
        textarea.style.height = 'auto';
        
        try {
            // Show animated typing indicator
            const loadingId = 'loading-' + Date.now();
            messageContainer.insertAdjacentHTML('beforeend', `
                <div id="${loadingId}" class="message ai-message animate-in">
                    <div class="message-avatar bot-avatar"><i class="fas fa-robot"></i></div>
                    <div class="message-content">
                        <div class="message-header">
                            <div class="message-sender">Expencio AI</div>
                            <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                        </div>
                        <div class="message-bubble">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            // Make the typing indicator visible with animation
            setTimeout(() => {
                document.getElementById(loadingId)?.classList.add('visible');
            }, 50);
            
            // Ensure messageContainer scrolls to show typing indicator
            messageContainer.scrollTop = messageContainer.scrollHeight;
            
            // Call your API
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            });
            
            // Remove loading indicator
            document.getElementById(loadingId)?.remove();
            
            if (!response.ok) {
                throw new Error(`API error: ${response.status}`);
            }
            
            const data = await response.json();
            let aiResponse = '';
            
            // Handle OpenRouter response format
            if (data.choices && data.choices[0]?.message?.content) {
                aiResponse = enhancedFormatResponse(data.choices[0].message.content);
            } 
            else if (data.error) {
                throw new Error(data.error);
            }
            else {
                throw new Error('Unexpected response format');
            }
            
            // Add with typing effect for shorter responses or normal for longer ones
            const wordCount = aiResponse.split(' ').length;
            if (wordCount < 50) {
                addMessageWithTypingEffect(aiResponse, 'ai');
            } else {
                addMessage(aiResponse, 'ai');
            }
            
        } catch (error) {
            console.error('Error:', error);
            addMessage(`Error: ${error.message}. Please try again.`, 'ai');
        }
    }
    
    /**
     * Enhanced formatting for AI responses with advanced styling
     */
    function enhancedFormatResponse(text) {
        // Process special financial elements first
        // Currency amounts
        text = text.replace(/\$\d+(\.\d{1,2})?/g, '<span class="currency">$&</span>');
        
        // Process percentages
        text = text.replace(/\d+(\.\d{1,2})?\%/g, '<span class="percentage">$&</span>');
        
        // Format markdown
        text = text
            // Headers
            .replace(/^### (.*$)/gm, '<h3>$1</h3>')
            .replace(/^## (.*$)/gm, '<h2>$1</h2>')
            .replace(/^# (.*$)/gm, '<h1>$1</h1>')
            
            // Formatting
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/~~(.*?)~~/g, '<del>$1</del>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            
            // Horizontal rules
            .replace(/^---$/gm, '<hr class="fancy-divider">')
            
            // Lists
            .replace(/^\s*\d+\.\s+(.*$)/gm, '<li class="numbered">$1</li>')
            .replace(/^\s*-\s+(.*$)/gm, '<li class="bullet">$1</li>')
            
            // Blockquotes
            .replace(/^>\s*(.*$)/gm, '<blockquote>$1</blockquote>')
            
            // Code blocks
            .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
        
        // Handle lists
        text = text
            .replace(/(<li class="numbered">.*<\/li>)+/g, '<ol class="enhanced-list">$&</ol>')
            .replace(/(<li class="bullet">.*<\/li>)+/g, '<ul class="enhanced-list">$&</ul>');
        
        // Handle financial advice sections with special formatting
        text = text.replace(/💡 TIP: (.*?)(?=\n\n|$)/gs, '<div class="finance-tip"><span class="tip-icon">💡</span><div class="tip-content">$1</div></div>');
        text = text.replace(/⚠️ WARNING: (.*?)(?=\n\n|$)/gs, '<div class="finance-warning"><span class="warning-icon">⚠️</span><div class="warning-content">$1</div></div>');
        
        // Handle tables
        if (text.includes('|')) {
            const tableRegex = /\|(.+)\|\n\|([-:]+\|)+\n(\|.+\|\n)+/g;
            text = text.replace(tableRegex, function(table) {
                // Process the table
                const rows = table.trim().split('\n');
                let html = '<div class="table-container"><table class="financial-table">';
                
                // Header
                const headerCells = rows[0].split('|').filter(cell => cell.trim() !== '');
                html += '<thead><tr>';
                headerCells.forEach(cell => {
                    html += `<th>${cell.trim()}</th>`;
                });
                html += '</tr></thead>';
                
                // Body
                html += '<tbody>';
                for (let i = 2; i < rows.length; i++) {
                    if (rows[i].trim() === '') continue;
                    
                    const cells = rows[i].split('|').filter(cell => cell.trim() !== '');
                    html += '<tr>';
                    cells.forEach(cell => {
                        // Highlight cells with currency or percentages
                        if (cell.includes('$') || cell.includes('%')) {
                            html += `<td class="highlight">${cell.trim()}</td>`;
                        } else {
                            html += `<td>${cell.trim()}</td>`;
                        }
                    });
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
                return html;
            });
        }
        
        return text;
    }
    
    /**
     * Add message with a typing effect
     */
    function addMessageWithTypingEffect(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message animate-in`;
        
        const avatar = sender === 'ai' ? 'robot' : 'user';
        const senderName = sender === 'ai' ? 'Expencio AI' : 'You';
        
        messageDiv.innerHTML = `
            <div class="message-avatar ${sender === 'ai' ? 'bot-avatar' : ''}">
                <i class="fas fa-${avatar}"></i>
            </div>
            <div class="message-content">
                <div class="message-header">
                    <div class="message-sender">${senderName}</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
                <div class="message-bubble">
                    <div class="message-text typing-text"></div>
                    <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
            </div>
        `;
        
        messageContainer.appendChild(messageDiv);
        messageContainer.scrollTop = messageContainer.scrollHeight;
        
        // Make message visible with animation
        setTimeout(() => {
            messageDiv.classList.add('visible');
        }, 50);
        
        const textElement = messageDiv.querySelector('.typing-text');
        const textToType = content;
        let i = 0;
        const typingSpeed = 15; // Adjust speed as needed
        
        function typeNextCharacter() {
            if (i < textToType.length) {
                if (textToType.substring(i, i + 4) === '<div' || 
                    textToType.substring(i, i + 5) === '</div' ||
                    textToType.substring(i, i + 4) === '<spa' ||
                    textToType.substring(i, i + 5) === '</spa' ||
                    textToType.substring(i, i + 3) === '<st' ||
                    textToType.substring(i, i + 4) === '</st' ||
                    textToType.substring(i, i + 2) === '</' ||
                    textToType.substring(i, i + 1) === '<') {
                    
                    // Find the closing '>' to add the whole tag at once
                    const endTagIndex = textToType.indexOf('>', i);
                    if (endTagIndex !== -1) {
                        textElement.innerHTML += textToType.substring(i, endTagIndex + 1);
                        i = endTagIndex + 1;
                    } else {
                        // If no closing tag found, just add the current character
                        textElement.innerHTML += textToType[i++];
                    }
                } else {
                    textElement.innerHTML += textToType[i++];
                }
                
                messageContainer.scrollTop = messageContainer.scrollHeight;
                setTimeout(typeNextCharacter, typingSpeed);
            }
        }
        
        typeNextCharacter();
    }
    
    function addMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message animate-in`;
        
        const avatar = sender === 'ai' ? 'robot' : 'user';
        const senderName = sender === 'ai' ? 'Expencio AI' : 'You';
        
        messageDiv.innerHTML = `
            <div class="message-avatar ${sender === 'ai' ? 'bot-avatar' : ''}">
                <i class="fas fa-${avatar}"></i>
            </div>
            <div class="message-content">
                <div class="message-header">
                    <div class="message-sender">${senderName}</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
                <div class="message-text">${content}</div>
            </div>
        `;
    
        messageContainer.appendChild(messageDiv);
        messageContainer.scrollTop = messageContainer.scrollHeight;
        
        // Trigger animation after a small delay
        setTimeout(() => {
            messageDiv.classList.add('visible');
        }, 50);
    }
    
    function showWelcomeMessage() {
        // Create the welcome message with proper styling
        const welcomeContent = `
            <div class="welcome-container">
                <div class="welcome-header">
                    <span class="welcome-icon">🤖 </span>
                    <h1>Welcome to Expencio AI Assistant!</h1>
                </div>
                <div class="welcome-description">
                    I'm your intelligent financial assistant, here to help you take control of your money and reach your financial goals.
                </div>
                <div class="welcome-services">
                    <h2>How can I help you?</h2>
                    <div class="services-grid">
                        <div class="service-item">
                            <span class="service-icon">📊</span>
                            <span class="service-text">Creating and managing budgets</span>
                        </div>
                        <div class="service-item">
                            <span class="service-icon">💰</span>
                            <span class="service-text">Tracking and categorizing expenses</span>
                        </div>
                        <div class="service-item">
                            <span class="service-icon">📈</span>
                            <span class="service-text">Offering smart saving and investment tips</span>
                        </div>
                        <div class="service-item">
                            <span class="service-icon">🧾</span>
                            <span class="service-text">Tax optimization advice</span>
                        </div>
                        <div class="service-item">
                            <span class="service-icon">🎯</span>
                            <span class="service-text">Personalized financial planning</span>
                        </div>
                        <div class="service-item">
                            <span class="service-icon">📱</span>
                            <span class="service-text">Mobile money management</span>
                        </div>
                    </div>
                </div>
                <div class="welcome-footer">
                    Just type your question or tell me what you'd like to do — I'm here to help!
                </div>
            </div>
        `;
        
        // Create the message element directly
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message ai-message animate-in';
        
        messageDiv.innerHTML = `
            <div class="message-avatar bot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="message-header">
                    <div class="message-sender">Expencio AI</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
                <div class="message-text">${welcomeContent}</div>
            </div>
        `;
        
        messageContainer.appendChild(messageDiv);
        messageContainer.scrollTop = messageContainer.scrollHeight;
        
        // Add animation class after a slight delay
        setTimeout(() => {
            messageDiv.classList.add('visible');
        }, 50);
    }
});