/**
 * RAG Chat Widget — KV Electronics
 * Semantic Search Chatbot with Knowledge Base
 * Level 3: Synonym Expansion + Weighted Scoring + Bilingual
 */
(function () {
    'use strict';

    /* ── State ── */
    var isOpen = false;
    var isSearching = false;
    var conversationHistory = [];
    var CHAT_HISTORY_KEY = 'rag_chat_history_v1';
    var currentLang = (function() {
        try { var s = localStorage.getItem('rag_chat_lang'); return (s === 'th') ? 'th' : 'en'; } catch(e) { return 'en'; }
    })();

    /* ── DOM refs (set after init) ── */
    var chatWindow, messagesArea, inputField, sendBtn, toggleBtn, langBtn;

    /* ── i18n strings ── */
    var i18n = {
        en: {
            title: 'KV Knowledge Base',
            subtitle: 'RAG-Powered Search • AI Assistant',
            placeholder: 'Ask about our products...',
            searchMode: 'Semantic Search: Products • Specs • Company Info',
            welcome: '<strong>\uD83D\uDC4B Welcome to KV Electronics Knowledge Base!</strong><br><br>' +
                'I can search our internal database to answer your questions about:<br>' +
                '• <strong>Products</strong> — Transformers, Inductors, Coils, Assemblies<br>' +
                '• <strong>Specifications</strong> — Size, voltage, standards, temperature<br>' +
                '• <strong>Company info</strong> — Contact, address, certifications<br><br>' +
                '<em style="font-size:12px;color:#64748b;">\uD83D\uDD0D Powered by RAG (Retrieval-Augmented Generation)</em>',
            suggestions: ['What products do you offer?', 'Transformer specifications', 'Contact information', 'RoHS compliance', 'Company info'],
            searched: '\uD83D\uDD0D Searched',
            items: 'items in',
            mode: 'Mode',
            synonyms: '\uD83D\uDD04 Synonyms',
            errSearch: '\u26A0\uFE0F Search error occurred.',
            errParse: '\u26A0\uFE0F Could not parse response. Please try again.',
            errConn: '\u26A0\uFE0F Connection error. Please try again.',
        },
        th: {
            title: 'ฐานความรู้ KV',
            subtitle: 'ค้นหาอัจฉริยะ • ผู้ช่วย AI',
            placeholder: 'ถามเกี่ยวกับสินค้าของเรา...',
            searchMode: 'ค้นหาเชิงความหมาย: สินค้า • สเปค • ข้อมูลบริษัท',
            welcome: '<strong>\uD83D\uDC4B ยินดีต้อนรับสู่ฐานความรู้ KV Electronics!</strong><br><br>' +
                'สามารถค้นหาข้อมูลจากฐานข้อมูลภายในของเราเกี่ยวกับ:<br>' +
                '• <strong>สินค้า</strong> — หม้อแปลง, ตัวเหนี่ยวนำ, คอยล์, ชุดประกอบ<br>' +
                '• <strong>สเปค</strong> — ขนาด, แรงดัน, มาตรฐาน, อุณหภูมิ<br>' +
                '• <strong>ข้อมูลบริษัท</strong> — ติดต่อ, ที่อยู่, ใบรับรอง<br><br>' +
                '<em style="font-size:12px;color:#64748b;">\uD83D\uDD0D ขับเคลื่อนด้วย RAG (Retrieval-Augmented Generation)</em>',
            suggestions: ['สินค้าทั้งหมดมีอะไรบ้าง?', 'สเปคหม้อแปลง', 'ข้อมูลติดต่อ', 'มาตรฐาน RoHS', 'เกี่ยวกับบริษัท'],
            searched: '\uD83D\uDD0D ค้นหา',
            items: 'รายการ ใน',
            mode: 'โหมด',
            synonyms: '\uD83D\uDD04 คำเหมือน',
            errSearch: '\u26A0\uFE0F เกิดข้อผิดพลาดในการค้นหา',
            errParse: '\u26A0\uFE0F ไม่สามารถอ่านผลลัพธ์ได้ กรุณาลองใหม่',
            errConn: '\u26A0\uFE0F การเชื่อมต่อผิดพลาด กรุณาลองใหม่',
        }
    };

    function t(key) { return i18n[currentLang][key] || i18n.en[key] || key; }

    /* ── Initialize ── */
    document.addEventListener('DOMContentLoaded', function () {
        buildChatUI();
        applyStoredLanguage();
        bindEvents();
        if (!loadConversationHistory()) {
            showWelcomeMessage();
        }
    });

    /* ── Build Chat UI ── */
    function buildChatUI() {
        // Toggle button
        var toggle = document.createElement('button');
        toggle.id = 'rag-chat-toggle';
        toggle.setAttribute('aria-label', 'Open AI Search Chat');
        toggle.innerHTML =
            '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' +
            '</svg>' +
            '<span class="rag-dot"></span>';
        toggleBtn = toggle;
        document.body.appendChild(toggle);

        // Chat window
        var win = document.createElement('div');
        win.id = 'rag-chat-window';
        win.innerHTML =
            '<div class="rag-header">' +
                '<div class="rag-header-avatar">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 14.5M14.25 3.104c.251.023.501.05.75.082M19.8 14.5l-2.147 2.147m0 0a2.25 2.25 0 01-3.182 0l-2.147-2.147m7.476 0a2.25 2.25 0 00-3.182 0M5 14.5l2.147 2.147m0 0a2.25 2.25 0 003.182 0l2.147-2.147m-7.476 0a2.25 2.25 0 013.182 0"/>' +
                    '</svg>' +
                '</div>' +
                '<div class="rag-header-info">' +
                    '<h4>KV Knowledge Base</h4>' +
                    '<span>RAG-Powered Search • AI Assistant</span>' +
                '</div>' +
                '<button class="rag-lang-btn" aria-label="Switch Language" title="Switch to Thai">' +
                    '<span class="rag-lang-label">TH</span>' +
                '</button>' +
                '<button class="rag-header-close" aria-label="Close">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>' +
                    '</svg>' +
                '</button>' +
            '</div>' +
            '<div class="rag-messages"></div>' +
            '<div class="rag-search-mode">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">' +
                '<path d="M8 0a8 8 0 100 16A8 8 0 008 0zm.93 4.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 4.588z"/><circle cx="8" cy="3.5" r=".75"/>' +
                '</svg>' +
                '<span>Semantic Search: Products • Specs • Company Info</span>' +
            '</div>' +
            '<div class="rag-input-area">' +
                '<input type="text" placeholder="Ask about our products..." autocomplete="off">' +
                '<button class="rag-send-btn" aria-label="Send">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">' +
                    '<path d="M15.964.686a.5.5 0 00-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 00-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 00.886-.083l6-14zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 00-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178z"/>' +
                    '</svg>' +
                '</button>' +
            '</div>';
        chatWindow = win;
        document.body.appendChild(win);

        messagesArea = win.querySelector('.rag-messages');
        inputField = win.querySelector('.rag-input-area input');
        sendBtn = win.querySelector('.rag-send-btn');
        langBtn = win.querySelector('.rag-lang-btn');
    }

    /* ── Bind Events ── */
    function bindEvents() {
        toggleBtn.addEventListener('click', function () {
            isOpen = !isOpen;
            chatWindow.classList.toggle('rag-open', isOpen);
            if (isOpen) {
                // Remove notification dot
                var dot = toggleBtn.querySelector('.rag-dot');
                if (dot) dot.remove();
                inputField.focus();
            }
        });

        chatWindow.querySelector('.rag-header-close').addEventListener('click', function () {
            isOpen = false;
            chatWindow.classList.remove('rag-open');
        });

        langBtn.addEventListener('click', function () {
            switchLanguage();
        });

        sendBtn.addEventListener('click', function () { sendMessage(); });
        inputField.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Delegate suggestion button clicks
        messagesArea.addEventListener('click', function (e) {
            var btn = e.target.closest('.rag-suggestion-btn');
            if (btn) {
                inputField.value = btn.textContent;
                sendMessage();
            }
        });
    }

    /* ── Apply stored language to UI (called once on init) ── */
    function applyStoredLanguage() {
        if (currentLang === 'th') {
            langBtn.querySelector('.rag-lang-label').textContent = 'EN';
            langBtn.setAttribute('title', 'เปลี่ยนเป็นภาษาอังกฤษ');
            chatWindow.querySelector('.rag-header-info h4').textContent = t('title');
            chatWindow.querySelector('.rag-header-info span').textContent = t('subtitle');
            inputField.setAttribute('placeholder', t('placeholder'));
            chatWindow.querySelector('.rag-search-mode span').textContent = t('searchMode');
        }
    }

    /* ── Switch Language ── */
    function switchLanguage() {
        currentLang = (currentLang === 'en') ? 'th' : 'en';
        try { localStorage.setItem('rag_chat_lang', currentLang); } catch(e) {}
        var nextLang = (currentLang === 'en') ? 'TH' : 'EN';
        var nextTitle = (currentLang === 'en') ? 'Switch to Thai' : 'เปลี่ยนเป็นภาษาอังกฤษ';
        langBtn.querySelector('.rag-lang-label').textContent = nextLang;
        langBtn.setAttribute('title', nextTitle);

        // Update header text
        chatWindow.querySelector('.rag-header-info h4').textContent = t('title');
        chatWindow.querySelector('.rag-header-info span').textContent = t('subtitle');

        // Update placeholder & search mode
        inputField.setAttribute('placeholder', t('placeholder'));
        chatWindow.querySelector('.rag-search-mode span').textContent = t('searchMode');

        // Re-show welcome
        messagesArea.innerHTML = '';
        conversationHistory = [];
        saveConversationHistory();
        showWelcomeMessage();
    }

    /* ── Show Welcome Message ── */
    function showWelcomeMessage() {
        addBotMessage(t('welcome'), t('suggestions'));
    }

    /* ── Send Message ── */
    function sendMessage() {
        var query = inputField.value.trim();
        if (!query || isSearching) return;

        addUserMessage(query);
        inputField.value = '';
        isSearching = true;
        sendBtn.disabled = true;
        showTyping();

        // AJAX call to RAG search API
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ragChatConfig.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                hideTyping();
                isSearching = false;
                sendBtn.disabled = false;

                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            displayRAGResponse(data.data);
                        } else {
                            addBotMessage('⚠️ ' + (data.data || t('errSearch')));
                        }
                    } catch (e) {
                        addBotMessage(t('errParse'));
                    }
                } else {
                    addBotMessage(t('errConn'));
                }
                inputField.focus();
            }
        };
        xhr.send(
            'action=rag_search' +
            '&nonce=' + encodeURIComponent(ragChatConfig.nonce) +
            '&query=' + encodeURIComponent(query) +
            '&lang=' + currentLang
        );
    }

    /* ── Display RAG Response ── */
    function displayRAGResponse(data) {
        var html = '';

        // Search metadata
        html += '<div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">' +
                t('searched') + ' ' + data.total_indexed + ' ' + t('items') + ' ' + data.search_time + 'ms' +
                ' • ' + t('mode') + ': <strong>' + data.search_mode + '</strong>' +
                '</div>';

        // Answer text from RAG
        if (data.answer) {
            html += '<div>' + data.answer + '</div>';
        }

        // Source documents
        if (data.sources && data.sources.length > 0) {
            data.sources.forEach(function (src) {
                var scoreClass = src.score >= 70 ? 'rag-score-high' : (src.score >= 40 ? 'rag-score-mid' : 'rag-score-low');
                html += '<a class="rag-source-card" href="' + escHtml(src.url) + '" target="_blank">' +
                    '<div class="rag-sc-title">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 01-2 2H4a2 2 0 01-2-2V2a2 2 0 012-2h5.5L14 4.5zM9.5 3A1.5 1.5 0 0011 4.5h2v9.255S12 12 8 12s-5 1.755-5 1.755V4.5A1.5 1.5 0 004.5 3v0h5z"/></svg>' +
                        escHtml(src.title) +
                        '<span class="rag-score ' + scoreClass + '">' + src.score + '%</span>' +
                    '</div>' +
                    (src.category ? '<div class="rag-sc-meta">📁 ' + escHtml(src.category) + '</div>' : '') +
                    (src.excerpt ? '<div class="rag-sc-excerpt">' + escHtml(src.excerpt) + '</div>' : '') +
                '</a>';
            });
        }

        // Synonym expansions used
        if (data.synonyms_used && data.synonyms_used.length > 0) {
            html += '<div style="font-size:11px;color:#94a3b8;margin-top:6px;">' +
                    t('synonyms') + ': ' + data.synonyms_used.join(', ') + '</div>';
        }

        var followup = data.suggestions || [];
        addBotMessage(html, followup);
    }

    /* ── Message Helpers ── */
    function addUserMessage(text) {
        var div = document.createElement('div');
        div.className = 'rag-msg rag-msg-user';
        div.textContent = text;
        messagesArea.appendChild(div);
        conversationHistory.push({ type: 'user', text: text });
        saveConversationHistory();
        scrollToBottom();
    }

    function addBotMessage(html, suggestions) {
        var div = document.createElement('div');
        div.className = 'rag-msg rag-msg-bot';
        div.innerHTML = html;

        if (suggestions && suggestions.length > 0) {
            var wrap = document.createElement('div');
            wrap.className = 'rag-suggestions';
            suggestions.forEach(function (s) {
                var btn = document.createElement('button');
                btn.className = 'rag-suggestion-btn';
                btn.textContent = s;
                wrap.appendChild(btn);
            });
            div.appendChild(wrap);
        }

        messagesArea.appendChild(div);
        conversationHistory.push({ type: 'bot', html: html, suggestions: suggestions || [] });
        saveConversationHistory();
        scrollToBottom();
    }

    function saveConversationHistory() {
        try {
            localStorage.setItem(CHAT_HISTORY_KEY, JSON.stringify(conversationHistory));
        } catch (e) {}
    }

    function loadConversationHistory() {
        try {
            var raw = localStorage.getItem(CHAT_HISTORY_KEY);
            if (!raw) return false;

            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed) || parsed.length === 0) return false;

            conversationHistory = [];
            messagesArea.innerHTML = '';

            parsed.forEach(function (item) {
                if (!item || !item.type) return;

                if (item.type === 'user' && typeof item.text === 'string') {
                    var userDiv = document.createElement('div');
                    userDiv.className = 'rag-msg rag-msg-user';
                    userDiv.textContent = item.text;
                    messagesArea.appendChild(userDiv);
                    conversationHistory.push({ type: 'user', text: item.text });
                }

                if (item.type === 'bot' && typeof item.html === 'string') {
                    var botDiv = document.createElement('div');
                    botDiv.className = 'rag-msg rag-msg-bot';
                    botDiv.innerHTML = item.html;

                    if (Array.isArray(item.suggestions) && item.suggestions.length > 0) {
                        var wrap = document.createElement('div');
                        wrap.className = 'rag-suggestions';
                        item.suggestions.forEach(function (s) {
                            var btn = document.createElement('button');
                            btn.className = 'rag-suggestion-btn';
                            btn.textContent = s;
                            wrap.appendChild(btn);
                        });
                        botDiv.appendChild(wrap);
                    }

                    messagesArea.appendChild(botDiv);
                    conversationHistory.push({ type: 'bot', html: item.html, suggestions: item.suggestions || [] });
                }
            });

            if (conversationHistory.length === 0) return false;
            scrollToBottom();
            return true;
        } catch (e) {
            return false;
        }
    }

    function showTyping() {
        var div = document.createElement('div');
        div.className = 'rag-typing';
        div.id = 'rag-typing-indicator';
        div.innerHTML = '<span></span><span></span><span></span>';
        messagesArea.appendChild(div);
        scrollToBottom();
    }

    function hideTyping() {
        var el = document.getElementById('rag-typing-indicator');
        if (el) el.remove();
    }

    function scrollToBottom() {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
})();
