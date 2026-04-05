// Chatbot Fix - Version forcée pour bypass cache
console.log('🔧 Chatbot Fix loaded - v1.0.0');

// Override the sendMessageToAI function
window.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Patching sendMessageToAI function...');
    
    // Wait for the original function to be defined
    setTimeout(() => {
        // Get original elements
        const chatInput = document.getElementById('chat-input');
        const chatSend = document.getElementById('chat-send');
        const chatMessages = document.getElementById('chat-messages');
        
        if (!chatInput || !chatSend || !chatMessages) {
            console.error('❌ Chat elements not found!');
            return;
        }
        
        console.log('✅ Chat elements found');
        
        // Store original sendMessageToAI if it exists
        const originalSendMessageToAI = window.sendMessageToAI;
        
        // Create new patched version
        window.sendMessageToAI = function(message, showInUI = false) {
            console.log('🚀 PATCHED sendMessageToAI called with:', message);
            
            if (showInUI) {
                addMessageToChat('user', message);
            }
            
            if (!window.chatHistory) {
                window.chatHistory = [];
                console.log('📝 Initialized chatHistory');
            }
            
            // Disable input while waiting
            chatInput.disabled = true;
            chatSend.disabled = true;
            
            // Show typing indicator
            showTypingIndicator();
            
            const startTime = Date.now();
            console.log(`⏱️ Request started at ${new Date().toLocaleTimeString()}`);
            
            // Create AbortController for timeout
            const abortController = new AbortController();
            
            // Safety timeout: 30 seconds
            const safetyTimeout = setTimeout(() => {
                const elapsed = Date.now() - startTime;
                console.error(`⏰ TIMEOUT after ${elapsed}ms`);
                abortController.abort();
                removeTypingIndicator();
                chatInput.disabled = false;
                chatSend.disabled = false;
                addMessageToChat('bot', `⚠️ Timeout après ${(elapsed/1000).toFixed(1)}s. La requête a été annulée.`);
            }, 30000);
            
            // Get form data
            const formData = {
                nomPatient: document.getElementById('reclamation_nomPatient')?.value || '',
                email: document.getElementById('reclamation_email')?.value || '',
                categorie: document.getElementById('reclamation_categorie')?.value || '',
                priorite: document.getElementById('reclamation_priorite')?.value || '',
                titre: document.getElementById('reclamation_titre')?.value || '',
                description: document.getElementById('reclamation_description')?.value || ''
            };
            
            const payload = {
                messages: window.chatHistory,
                formData: formData
            };
            
            console.log('📦 Payload:', JSON.stringify(payload, null, 2));
            console.log('🌐 Sending to: /api/chatbot/reclamation');
            
            // Send to API
            fetch('/api/chatbot/reclamation', {
                signal: abortController.signal,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                const elapsed = Date.now() - startTime;
                console.log(`✅ Response received in ${elapsed}ms`);
                console.log('📊 Status:', response.status, response.statusText);
                console.log('📋 Content-Type:', response.headers.get('Content-Type'));
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const elapsed = Date.now() - startTime;
                console.log(`✅ Data parsed in ${elapsed}ms`);
                console.log('📦 Response data:', data);
                
                clearTimeout(safetyTimeout);
                removeTypingIndicator();
                
                // Check if API is not configured
                if (data.not_configured) {
                    addMessageToChat('bot', data.message || '⚙️ L\'assistant IA n\'est pas encore configuré.');
                    chatInput.disabled = false;
                    chatSend.disabled = false;
                    return;
                }
                
                if (data.message) {
                    // Add bot response
                    addMessageToChat('bot', data.message);
                    window.chatHistory.push({ role: 'assistant', content: data.message });
                    
                    // Store suggestions
                    if (data.suggestions && Object.keys(data.suggestions).length > 0) {
                        window.currentSuggestions = data.suggestions;
                        document.getElementById('autofill-btn')?.classList.add('show');
                        console.log('💡 Suggestions:', data.suggestions);
                    }
                    
                    // Display sentiment
                    if (data.sentiment) {
                        window.currentSentiment = data.sentiment;
                        displaySentiment(data.sentiment);
                        console.log('😊 Sentiment:', data.sentiment);
                    }
                } else if (data.error) {
                    addMessageToChat('bot', '⚠️ Erreur: ' + data.error);
                    console.error('❌ Server error:', data.error);
                } else {
                    addMessageToChat('bot', '⚠️ Réponse invalide du serveur.');
                    console.error('❌ Invalid response:', data);
                }
                
                // Re-enable input
                chatInput.disabled = false;
                chatSend.disabled = false;
                chatInput.focus();
            })
            .catch(error => {
                const elapsed = Date.now() - startTime;
                console.error(`❌ Error after ${elapsed}ms:`, error);
                console.error('Error name:', error.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                
                clearTimeout(safetyTimeout);
                removeTypingIndicator();
                
                // Check if request was aborted by timeout
                if (error.name === 'AbortError') {
                    console.log('⏰ Request was aborted by timeout handler');
                    return;
                }
                
                let errorMessage = '⚠️ Erreur de connexion au serveur.';
                if (error.message.includes('HTTP error')) {
                    errorMessage = `⚠️ Erreur serveur (${error.message})`;
                } else if (error.message.includes('Failed to fetch')) {
                    errorMessage = '⚠️ Impossible de contacter le serveur.';
                }
                
                addMessageToChat('bot', errorMessage);
                chatInput.disabled = false;
                chatSend.disabled = false;
            });
        };
        
        console.log('✅ sendMessageToAI has been patched!');
    }, 1000);
});
