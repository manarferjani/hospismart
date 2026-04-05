/**
 * Chatbot Direct Gemini - v2.0
 * Appel DIRECT à l'API Gemini depuis le navigateur
 * Contourne le serveur PHP mono-thread
 */
(function() {
    'use strict';
    console.log('🤖 Chatbot Direct Gemini v2.0 loaded');
    
    const GEMINI_KEY = 'AIzaSyCN57ucyicr2EcMP9lIYKKnPH7f1N_A2k0';
    const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' + GEMINI_KEY;

    document.addEventListener('DOMContentLoaded', function() {
        console.log('🤖 Initializing chatbot...');
        
        // Elements
        const chatMessages = document.getElementById('chatbot-messages');
        const chatInput = document.getElementById('chatbot-input');
        const chatSend = document.getElementById('chatbot-send');
        const autofillBtn = document.getElementById('autofill-btn');
        const sentimentIndicator = document.getElementById('sentiment-indicator');
        const sentimentText = document.getElementById('sentiment-text');
        const modeSelector = document.getElementById('mode-selector');
        const modeButtons = document.querySelectorAll('.btn-mode');
        
        // Form fields
        const fieldNomPatient = document.getElementById('field-nomPatient');
        const fieldEmail = document.getElementById('field-email');
        const fieldCategorie = document.getElementById('field-categorie');
        const fieldPriorite = document.getElementById('field-priorite');
        const fieldTitre = document.getElementById('field-titre');
        const fieldDescription = document.getElementById('field-description');
        
        if (!chatInput || !chatSend || !chatMessages) {
            console.error('❌ Chat elements NOT found, aborting');
            return;
        }
        console.log('✅ All chat elements found');
        
        // State
        let chatHistory = [];
        let currentSuggestions = null;
        let currentSentiment = null;
        
        // ============================================================
        // MODE SELECTION
        // ============================================================
        modeButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const selectedMode = this.dataset.mode;
                if (modeSelector) modeSelector.classList.remove('show');
                
                let modeMessage = '';
                let botResponse = '';
                
                if (selectedMode === 'guided') {
                    modeMessage = 'MODE GUIDÉ : Je souhaite être guidé étape par étape pour remplir ma réclamation.';
                    botResponse = '✅ Mode Guidé activé ! Je vais vous poser des questions une par une pour vous aider. Commençons : Pouvez-vous me dire comment vous vous appelez ?';
                } else if (selectedMode === 'free') {
                    modeMessage = 'MODE LIBRE : Je vais décrire ma situation en détail.';
                    botResponse = '✅ Mode Libre activé ! Décrivez-moi votre situation de manière complète et je générerai ensuite toutes les suggestions pour remplir votre réclamation.';
                } else if (selectedMode === 'chat') {
                    modeMessage = 'DISCUSSION LIBRE : Je souhaite discuter librement de ma situation.';
                    botResponse = '✅ Discussion Libre activée ! N\'hésitez pas à me parler de votre situation, je suis là pour vous écouter et vous aider.';
                }
                
                // Add mode notification
                const modeNotif = document.createElement('div');
                modeNotif.style.cssText = 'text-align:center;padding:8px;margin:8px 0;font-size:0.8rem;color:#6c757d;font-style:italic;';
                modeNotif.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + (this.querySelector('.mode-title')?.textContent || selectedMode) + ' activé';
                chatMessages.appendChild(modeNotif);
                
                addMessage('bot', botResponse);
                chatHistory.push({ role: 'user', content: modeMessage });
                chatHistory.push({ role: 'assistant', content: botResponse });
                chatInput.focus();
            });
        });
        
        // ============================================================
        // SEND MESSAGE
        // ============================================================
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        chatSend.addEventListener('click', sendMessage);
        
        if (autofillBtn) {
            autofillBtn.addEventListener('click', function() {
                if (currentSuggestions) applyAutoFill(currentSuggestions);
            });
        }
        
        function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;
            
            chatInput.value = '';
            addMessage('user', message);
            chatHistory.push({ role: 'user', content: message });
            
            callGeminiDirect();
        }
        
        // ============================================================
        // DIRECT GEMINI API CALL (no PHP backend!)
        // ============================================================
        function callGeminiDirect() {
            chatInput.disabled = true;
            chatSend.disabled = true;
            showTyping();
            
            const startTime = Date.now();
            console.log('🚀 Calling Gemini API DIRECTLY from browser...');
            
            // Build contents for Gemini
            const contents = [];
            chatHistory.forEach(function(msg) {
                const role = msg.role === 'user' ? 'user' : 'model';
                if (msg.content && msg.content.trim()) {
                    contents.push({ role: role, parts: [{ text: msg.content }] });
                }
            });
            
            // Ensure alternating user/model pattern
            if (contents.length > 0 && contents[0].role === 'model') {
                contents.unshift({ role: 'user', parts: [{ text: 'Bonjour' }] });
            }
            
            const payload = {
                systemInstruction: { parts: [{ text: buildSystemPrompt() }] },
                contents: contents,
                generationConfig: { temperature: 0.75, maxOutputTokens: 800, topP: 0.95 }
            };
            
            console.log('📦 Sending', contents.length, 'messages to Gemini');
            
            const abortCtrl = new AbortController();
            const timeout = setTimeout(function() {
                abortCtrl.abort();
                hideTyping();
                chatInput.disabled = false;
                chatSend.disabled = false;
                addMessage('bot', '⚠️ Timeout après 30 secondes. Veuillez réessayer.');
            }, 30000);
            
            fetch(GEMINI_URL, {
                signal: abortCtrl.signal,
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(response) {
                console.log('✅ Gemini status:', response.status, 'in', (Date.now() - startTime) + 'ms');
                if (!response.ok) {
                    return response.text().then(function(txt) {
                        throw new Error('Gemini API ' + response.status + ': ' + txt.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(function(data) {
                clearTimeout(timeout);
                hideTyping();
                
                const text = data && data.candidates && data.candidates[0] && 
                             data.candidates[0].content && data.candidates[0].content.parts && 
                             data.candidates[0].content.parts[0] ? data.candidates[0].content.parts[0].text : '';
                
                console.log('💬 Response length:', text.length, 'chars');
                
                if (!text) {
                    addMessage('bot', '⚠️ Réponse vide. Veuillez réessayer.');
                    chatInput.disabled = false;
                    chatSend.disabled = false;
                    return;
                }
                
                // Extract suggestions and sentiment BEFORE cleaning
                const sugg = extractSuggestions(text);
                const sent = extractSentiment(text);
                const clean = cleanMessage(text);
                
                addMessage('bot', clean);
                chatHistory.push({ role: 'assistant', content: text });
                
                if (sugg && Object.keys(sugg).length > 0) {
                    currentSuggestions = sugg;
                    console.log('💡 Suggestions:', sugg);
                    // Auto-fill immediately
                    applyAutoFill(sugg);
                }
                
                if (sent) {
                    currentSentiment = sent;
                    showSentiment(sent);
                    var etatField = document.getElementById('etat-mental-field');
                    if (etatField) etatField.value = sent.etat + ' — ' + sent.explication;
                    console.log('😊 Sentiment:', sent.etat, '-', sent.explication);
                }
                
                chatInput.disabled = false;
                chatSend.disabled = false;
                chatInput.focus();
            })
            .catch(function(err) {
                clearTimeout(timeout);
                hideTyping();
                console.error('❌ Error:', err);
                if (err.name !== 'AbortError') {
                    addMessage('bot', '⚠️ Erreur: ' + err.message);
                }
                chatInput.disabled = false;
                chatSend.disabled = false;
            });
        }
        
        // ============================================================
        // SYSTEM PROMPT
        // ============================================================
        function buildSystemPrompt() {
            const fc = [];
            if (fieldTitre && fieldTitre.value) fc.push('Titre: ' + fieldTitre.value);
            if (fieldCategorie && fieldCategorie.value) fc.push('Catégorie: ' + fieldCategorie.value);
            if (fieldPriorite && fieldPriorite.value) fc.push('Priorité: ' + fieldPriorite.value);
            if (fieldDescription && fieldDescription.value) fc.push('Description: ' + fieldDescription.value);
            var ctx = fc.length > 0 ? fc.join('\n') : 'Formulaire vide';
            
            return 'Tu es un assistant virtuel médical bienveillant d\'HospiSmart.\n' +
                'Aide les patients à remplir leur formulaire de réclamation.\n\n' +
                'CONTEXTE DU FORMULAIRE:\n' + ctx + '\n\n' +
                'CHAMPS DU FORMULAIRE:\n' +
                '- Titre: court et descriptif (5-255 chars)\n' +
                '- Catégorie: une valeur EXACTE parmi: "Service médical", "Accueil", "Facturation", "Hygiène", "Autre"\n' +
                '- Priorité: une valeur EXACTE parmi: "Basse", "Normale", "Haute", "Urgente"\n' +
                '- Description: explication détaillée (min 10 chars)\n\n' +
                'MODES:\n' +
                '- GUIDÉ: questions une par une\n' +
                '- LIBRE: patient décrit tout, tu suggères\n' +
                '- CONVERSATIONNEL: discussion ouverte\n\n' +
                'RÈGLE ABSOLUE - FORMAT DE SORTIE:\n' +
                'À CHAQUE réponse, tu DOIS terminer par ces 5 lignes EXACTEMENT dans ce format (sans aucune modification des noms de clés):\n\n' +
                'TITRE_SUGGERE: "un titre"\n' +
                'CATEGORIE_SUGGEREE: "une catégorie"\n' +
                'PRIORITE_SUGGEREE: "une priorité"\n' +
                'DESCRIPTION_SUGGEREE: "une description"\n' +
                'ETAT_MENTAL: "un état" — une explication\n\n' +
                'INSTRUCTIONS STRICTES:\n' +
                '- Réponds en français, empathique, professionnel, concis (2-3 phrases max)\n' +
                '- Les 5 lignes de marqueurs ci-dessus sont OBLIGATOIRES à chaque réponse, même si le patient n\'a donné que peu d\'infos\n' +
                '- N\'utilise JAMAIS d\'accents dans les noms des marqueurs: écris TITRE_SUGGERE, pas TITRE_SUGGÉRÉ\n' +
                '- Pour la catégorie, utilise UNIQUEMENT: "Service médical", "Accueil", "Facturation", "Hygiène" ou "Autre"\n' +
                '- Pour la priorité, utilise UNIQUEMENT: "Basse", "Normale", "Haute" ou "Urgente"\n' +
                '- PAS de conseils médicaux\n' +
                '- Les marqueurs doivent être sur des lignes SÉPARÉES à la FIN de ta réponse\n\n' +
                'ÉTATS MENTAUX possibles pour ETAT_MENTAL:\n' +
                '- "Calme" — patient posé\n' +
                '- "Frustré" — patient agacé\n' +
                '- "En colère" — patient très mécontent\n' +
                '- "Anxieux" — patient inquiet\n' +
                '- "Triste" — patient abattu\n' +
                '- "Satisfait" — patient content\n\n' +
                'EXEMPLE COMPLET de fin de réponse:\n' +
                'TITRE_SUGGERE: "Erreur de facturation consultation"\n' +
                'CATEGORIE_SUGGEREE: "Facturation"\n' +
                'PRIORITE_SUGGEREE: "Haute"\n' +
                'DESCRIPTION_SUGGEREE: "Le patient a reçu une facture incorrecte pour sa consultation du 15 mars."\n' +
                'ETAT_MENTAL: "Frustré" — Le patient montre de la frustration face à l\'erreur de facturation. Conseil: Adoptez un ton compréhensif.';
        }
        
        // ============================================================
        // EXTRACTION (handles accented variants from Gemini)
        // ============================================================
        function extractSuggestions(text) {
            var s = {}, m;
            // Handle: TITRE_SUGGERE, TITRE_SUGGÉRÉ, TITRE SUGGERE, TITRE SUGGÉRÉ, **TITRE_SUGGERE**, etc.
            if ((m = text.match(/\*{0,2}TITRE[_ ]SUGG[EÉ]R[EÉ]\*{0,2}\s*:?\s*["«]?([^"»\n]+)["»]?/i))) s.titre = m[1].trim();
            if ((m = text.match(/\*{0,2}CAT[EÉ]GORIE[_ ]SUGG[EÉ]R[EÉ]{1,2}\*{0,2}\s*:?\s*["«]?([^"»\n]+)["»]?/i))) s.categorie = m[1].trim();
            if ((m = text.match(/\*{0,2}PRIORIT[EÉ][_ ]SUGG[EÉ]R[EÉ]{1,2}\*{0,2}\s*:?\s*["«]?([^"»\n]+)["»]?/i))) s.priorite = m[1].trim();
            if ((m = text.match(/\*{0,2}DESCRIPTION[_ ]SUGG[EÉ]R[EÉ]{1,2}\*{0,2}\s*:?\s*["«]?([^"»\n]+)["»]?/i))) s.description = m[1].trim().replace(/\n+/g, ' ');
            
            console.log('🔍 extractSuggestions from last 600 chars:', text.substring(Math.max(0, text.length - 600)));
            console.log('🔍 Result:', JSON.stringify(s));
            return Object.keys(s).length > 0 ? s : null;
        }
        
        function extractSentiment(text) {
            // Handle: ETAT_MENTAL, ÉTAT_MENTAL, ETAT MENTAL, ÉTAT MENTAL, **ETAT_MENTAL**, etc.
            var m = text.match(/\*{0,2}[EÉ]TAT[_ ]MENTAL\*{0,2}\s*:?\s*["«]?([^"»—–\-\n]+)["»]?\s*[—\-–]\s*(.+?)(?:\n|$)/i);
            if (!m) return null;
            var etat = m[1].trim();
            var expl = m[2].trim();
            var valid = ['Calme','Frustré','En colère','Anxieux','Triste','Satisfait'];
            var found = valid.find(function(v) { return v.toLowerCase() === etat.toLowerCase(); });
            
            var conseil = '';
            var conseilMatch = expl.match(/Conseil\s*:\s*(.+)/i);
            if (conseilMatch) {
                conseil = conseilMatch[1].trim();
            }
            
            return { etat: found || 'Calme', explication: expl, conseil: conseil };
        }
        
        function cleanMessage(text) {
            return text
                .replace(/\*{0,2}TITRE[_ ]SUGG[EÉ]R[EÉ]\*{0,2}\s*:?\s*["«]?[^"»\n]+["»]?/gi, '')
                .replace(/\*{0,2}CAT[EÉ]GORIE[_ ]SUGG[EÉ]R[EÉ]{1,2}\*{0,2}\s*:?\s*["«]?[^"»\n]+["»]?/gi, '')
                .replace(/\*{0,2}PRIORIT[EÉ][_ ]SUGG[EÉ]R[EÉ]{1,2}\*{0,2}\s*:?\s*["«]?[^"»\n]+["»]?/gi, '')
                .replace(/\*{0,2}DESCRIPTION[_ ]SUGG[EÉ]R[EÉ]{1,2}\*{0,2}\s*:?\s*["«]?[^"»\n]+["»]?/gi, '')
                .replace(/\*{0,2}[EÉ]TAT[_ ]MENTAL\*{0,2}\s*:?\s*["«]?[^"»—–\-\n]+["»]?\s*[—\-–]\s*.+/gi, '')
                .replace(/\n{2,}/g, '\n\n')
                .trim();
        }
        
        // ============================================================
        // UI HELPERS
        // ============================================================
        function addMessage(role, text) {
            var div = document.createElement('div');
            div.className = 'chatbot-message ' + role;
            
            var avatar = document.createElement('div');
            avatar.className = 'avatar';
            avatar.innerHTML = role === 'bot' 
                ? '<i class="fa-solid fa-robot"></i>' 
                : '<i class="fa-solid fa-user"></i>';
            
            var bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.textContent = text;
            
            div.appendChild(avatar);
            div.appendChild(bubble);
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function showTyping() {
            var existing = document.getElementById('typing-indicator');
            if (existing) existing.remove();
            var div = document.createElement('div');
            div.id = 'typing-indicator';
            div.className = 'chatbot-message bot';
            div.innerHTML = '<div class="avatar"><i class="fa-solid fa-robot"></i></div>' +
                           '<div class="bubble typing-dots"><span></span><span></span><span></span></div>';
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function hideTyping() {
            var el = document.getElementById('typing-indicator');
            if (el) el.remove();
        }
        
        function showSentiment(s) {
            if (!sentimentIndicator || !sentimentText) return;
            var emojis = {
                'Calme': '😌', 'Frustré': '😤', 'En colère': '😡',
                'Anxieux': '😰', 'Triste': '😢', 'Satisfait': '😊'
            };
            var colors = {
                'Calme': '#28a745', 'Frustré': '#fd7e14', 'En colère': '#dc3545',
                'Anxieux': '#ffc107', 'Triste': '#6f42c1', 'Satisfait': '#20c997'
            };
            sentimentIndicator.style.display = 'flex';
            sentimentIndicator.style.borderLeftColor = colors[s.etat] || '#6c757d';
            sentimentText.textContent = (emojis[s.etat] || '❓') + ' ' + s.etat + ' — ' + s.explication;
        }
        
        function applyAutoFill(sugg) {
            var filled = [];
            if (sugg.titre && fieldTitre) {
                fieldTitre.value = sugg.titre;
                fieldTitre.style.borderColor = '#28a745';
                setTimeout(function() { fieldTitre.style.borderColor = ''; }, 2000);
                filled.push('Titre');
            }
            if (sugg.description && fieldDescription) {
                fieldDescription.value = sugg.description;
                fieldDescription.style.borderColor = '#28a745';
                setTimeout(function() { fieldDescription.style.borderColor = ''; }, 2000);
                filled.push('Description');
            }
            if (sugg.categorie && fieldCategorie) {
                var catVal = sugg.categorie.trim();
                var catFound = false;
                for (var i = 0; i < fieldCategorie.options.length; i++) {
                    var optText = fieldCategorie.options[i].text.trim();
                    var optVal = fieldCategorie.options[i].value.trim();
                    if (optText.toLowerCase() === catVal.toLowerCase() || optVal.toLowerCase() === catVal.toLowerCase()) {
                        fieldCategorie.selectedIndex = i;
                        catFound = true;
                        break;
                    }
                }
                if (catFound) {
                    fieldCategorie.style.borderColor = '#28a745';
                    setTimeout(function() { fieldCategorie.style.borderColor = ''; }, 2000);
                    filled.push('Catégorie');
                }
            }
            if (sugg.priorite && fieldPriorite) {
                var prioVal = sugg.priorite.trim();
                var prioFound = false;
                for (var i = 0; i < fieldPriorite.options.length; i++) {
                    var optText = fieldPriorite.options[i].text.trim();
                    var optVal = fieldPriorite.options[i].value.trim();
                    if (optText.toLowerCase() === prioVal.toLowerCase() || optVal.toLowerCase() === prioVal.toLowerCase()) {
                        fieldPriorite.selectedIndex = i;
                        prioFound = true;
                        break;
                    }
                }
                if (prioFound) {
                    fieldPriorite.style.borderColor = '#28a745';
                    setTimeout(function() { fieldPriorite.style.borderColor = ''; }, 2000);
                    filled.push('Priorité');
                }
            }
            if (filled.length > 0) {
                addMessage('bot', '✅ Champs remplis automatiquement : ' + filled.join(', ') + '. Vérifiez et ajustez si nécessaire.');
            }
            if (autofillBtn) autofillBtn.classList.remove('show');
        }
        
        console.log('🤖 Chatbot Direct Gemini v2.0 ready!');
    });
})();
