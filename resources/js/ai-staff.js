/**
 * AI Staff panel. Vanilla JS, no framework: sends visitor messages to the
 * Laravel backend and renders both the reply text and the structured
 * ui_payload the AI's tools attach (service cards, project cards, lead
 * confirmations, handover notices) as real DOM — never as raw HTML, since
 * every string here comes from the model, the database, or a staff reply.
 */

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
}

function safeUrl(url) {
    if (!url) return null;
    if (url.startsWith('/')) return url;
    try {
        const parsed = new URL(url);
        return ['http:', 'https:'].includes(parsed.protocol) ? parsed.href : null;
    } catch {
        return null;
    }
}

/** Escapes, then allows only **bold** and line breaks from the model. */
function formatReply(text) {
    const escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    return escaped
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/^#{1,6}\s*/gm, '')
        .replace(/\n/g, '<br>');
}

function initAiStaff() {
    const panel = document.querySelector('[data-staff-panel]');
    if (!panel) return;

    const config = JSON.parse(panel.querySelector('[data-staff-config]').textContent);
    const copy = config.copy;

    const messagesEl = panel.querySelector('[data-messages]');
    const formEl = panel.querySelector('[data-chat-form]');
    const inputEl = panel.querySelector('[data-chat-input]');
    const submitEl = panel.querySelector('[data-chat-submit]');
    const banner = panel.querySelector('[data-status-banner]');
    const characterStatus = panel.querySelector('[data-character-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const mobileQuery = window.matchMedia('(max-width: 1023px)');
    const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    let panelTrigger = null;
    let closingPanel = false;

    let ready = false;
    let busy = false;
    let handedOver = false;
    let renderedIds = new Set();
    let pollTimer = null;
    let typingEl = null;
    let revealTimer = null;
    let finishReveal = null;

    // --- animated character: every copy on the page mirrors the chat state ---
    const characters = [...document.querySelectorAll('[data-staff-character]')];
    let characterTimer = null;

    function restingState() {
        return handedOver ? 'handover' : 'idle';
    }

    function setCharacter(state, duration) {
        clearTimeout(characterTimer);
        characters.forEach((node) => { node.dataset.state = state; });
        panel.dataset.characterState = state;
        if (characterStatus) characterStatus.textContent = copy[`state_${state}`] || copy.state_idle;
        if (duration) characterTimer = setTimeout(() => setCharacter(restingState()), duration);
    }

    // --- voice: the browser's own speech synthesis, opt-in per visitor ---
    const voiceButton = panel.querySelector('[data-voice-toggle]');
    const voiceKey = `${config.storageKey}_voice`;
    const voice = {
        supported: 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window,
        enabled: false,
        lang: { id: 'id-ID', en: 'en-US', ja: 'ja-JP' }[config.locale] || 'id-ID',
    };

    function pickVoice() {
        const voices = window.speechSynthesis.getVoices();
        const wanted = voice.lang.toLowerCase();
        const exact = voices.filter((v) => v.lang.replace('_', '-').toLowerCase() === wanted);
        const pool = exact.length ? exact : voices.filter((v) => v.lang.toLowerCase().startsWith(wanted.slice(0, 2)));
        return pool.find((v) => /google|natural|premium|enhanced|siri/i.test(v.name)) || pool[0] || null;
    }

    /** Strips markdown and emoji so they are not read aloud. */
    function speakable(text) {
        return text
            .replace(/\*\*/g, '')
            .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE0F}]/gu, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function speak(text, onEnd) {
        if (!voice.enabled || !voice.supported || !text) return false;
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(speakable(text));
        utterance.lang = voice.lang;
        const chosen = pickVoice();
        if (chosen) utterance.voice = chosen;
        utterance.rate = 1.02;
        utterance.pitch = 1.05;
        let finished = false;
        const finish = () => { if (!finished) { finished = true; onEnd?.(); } };
        utterance.onend = finish;
        utterance.onerror = finish;
        window.speechSynthesis.speak(utterance);
        // some engines never fire onend for cancelled speech — never leave the character stuck
        setTimeout(finish, Math.min(4000 + text.length * 90, 45000));
        return true;
    }

    function stopSpeaking() {
        if (voice.supported) window.speechSynthesis.cancel();
    }

    function setVoice(on) {
        voice.enabled = on;
        if (voiceButton) {
            voiceButton.setAttribute('aria-pressed', String(on));
            voiceButton.classList.toggle('is-on', on);
            voiceButton.title = on ? copy.voice_on : copy.voice_off;
            voiceButton.setAttribute('aria-label', voiceButton.title);
        }
        try { localStorage.setItem(voiceKey, on ? '1' : '0'); } catch { /* private mode */ }
        if (!on) stopSpeaking();
    }

    voiceButton?.addEventListener('click', () => {
        if (!voice.supported) {
            attachment([noticeCard(copy.voice_unsupported)]);
            scrollToBottom();
            return;
        }
        setVoice(!voice.enabled);
        if (voice.enabled) {
            // the click is the user activation browsers require before speaking
            setCharacter('talking');
            speak(copy.chat_intro, () => setCharacter(restingState()));
        }
    });

    if (voice.supported) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.addEventListener?.('voiceschanged', () => window.speechSynthesis.getVoices());
        try { if (localStorage.getItem(voiceKey) === '1') setVoice(true); } catch { /* private mode */ }
        window.addEventListener('pagehide', stopSpeaking);
    }

    /** Talks for as long as the reply is spoken, or as long as the text takes to reveal. */
    function reactToReply(message, revealed) {
        const types = (message.ui_payload || []).map((payload) => payload.type);
        const afterwards = types.includes('lead_confirmation')
            ? () => setCharacter('happy', 3500)
            : () => setCharacter(restingState());

        setCharacter('talking');
        if (speak(message.content, afterwards)) return;

        if (revealed) {
            revealed.then(() => { characterTimer = setTimeout(afterwards, 400); });
            return;
        }

        const talkFor = Math.min(1500 + (message.content?.length || 0) * 28, 6000);
        characterTimer = setTimeout(afterwards, types.includes('handover') ? 2000 : talkFor);
    }

    // --- proactive greeting: once per session, when the visitor has gone quiet ---
    const NUDGE_NOT_BEFORE_MS = 20000;
    const NUDGE_IDLE_MS = 8000;
    const nudgeKey = `${config.storageKey}_nudged`;
    let lastActivity = Date.now();
    let nudged = false;
    try { nudged = sessionStorage.getItem(nudgeKey) === '1'; } catch { /* private mode */ }

    ['pointerdown', 'keydown', 'scroll', 'touchstart', 'wheel'].forEach((type) => {
        window.addEventListener(type, () => { lastActivity = Date.now(); }, { passive: true });
    });

    function currentSection() {
        return document.querySelector('[data-scrollspy] .is-current')?.dataset.nav || '';
    }

    function showNudgeToast(text) {
        document.querySelector('.staff-nudge')?.remove();
        const toast = el('button', 'staff-nudge');
        toast.type = 'button';
        toast.appendChild(el('span', 'staff-nudge-avatar'));
        toast.appendChild(el('span', 'staff-nudge-text', text));
        toast.addEventListener('click', () => { toast.remove(); openPanel(); });
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 15000);
    }

    function maybeNudge() {
        if (nudged || !ready || busy || handedOver || document.hidden) return;
        if (messagesEl.querySelector('.msg-visitor')) { nudged = true; return; }
        if (document.activeElement === inputEl || Date.now() - lastActivity < NUDGE_IDLE_MS) return;

        nudged = true;
        try { sessionStorage.setItem(nudgeKey, '1'); } catch { /* private mode */ }

        const key = { services: 'nudge_services', how: 'nudge_services', projects: 'nudge_projects', pricing: 'nudge_pricing', about: 'nudge_about', contact: 'nudge_contact' }[currentSection()] || 'nudge_general';
        const text = copy[key];

        textBubble('assistant', text);
        scrollToBottom();
        const speech = document.querySelector('[data-hero-speech]');
        if (speech) speech.textContent = text;
        setCharacter('talking');
        if (!speak(text, () => setCharacter(restingState()))) characterTimer = setTimeout(() => setCharacter(restingState()), 3500);
        if (mobileQuery.matches && !panel.classList.contains('is-open')) showNudgeToast(text);
    }

    setTimeout(() => setInterval(maybeNudge, 2000), NUDGE_NOT_BEFORE_MS);

    // --- panel open/close (only meaningful on mobile; desktop is always open) ---

    function openPanel() {
        if (!panel.classList.contains('is-open')) {
            panelTrigger = document.activeElement === inputEl ? panel.querySelector('[data-open-staff]') : document.activeElement;
        }
        panel.classList.add('is-open');
        document.body.classList.add('staff-open');
        updateChatViewport();
        if (!mobileQuery.matches) {
            panel.scrollIntoView({ behavior: reducedMotionQuery.matches ? 'instant' : 'smooth', block: 'center' });
        }
        scrollToBottom();
        inputEl.focus({ preventScroll: true });
    }

    function closePanel() {
        closingPanel = true;
        if (document.activeElement === inputEl) inputEl.blur();
        panel.classList.remove('is-open');
        document.body.classList.remove('staff-open');
        panelTrigger?.focus({ preventScroll: true });
        closingPanel = false;
    }

    mobileQuery.addEventListener('change', closePanel);

    function updateChatViewport() {
        const viewport = window.visualViewport;
        if (!mobileQuery.matches || !panel.classList.contains('is-open') || (viewport && viewport.scale !== 1)) return;
        panel.style.setProperty('--chat-viewport-height', `${viewport?.height || window.innerHeight}px`);
        panel.style.setProperty('--chat-viewport-top', `${viewport?.offsetTop || 0}px`);
        panel.classList.toggle('keyboard-open', Boolean(viewport && window.innerHeight - viewport.height > 120));
        if (document.activeElement === inputEl) scrollToBottom();
    }

    window.visualViewport?.addEventListener('resize', updateChatViewport);
    window.visualViewport?.addEventListener('scroll', updateChatViewport);
    window.addEventListener('resize', updateChatViewport);

    document.querySelectorAll('[data-open-staff]').forEach((button) => button.addEventListener('click', openPanel));
    panel.querySelector('[data-close-staff]')?.addEventListener('click', closePanel);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open') && mobileQuery.matches) closePanel();
        if (event.key !== 'Tab' || !mobileQuery.matches || !panel.classList.contains('is-open')) return;

        const controls = [...panel.querySelectorAll('button:not(:disabled), textarea:not(:disabled), a[href]')]
            .filter((node) => node.getClientRects().length);
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    // --- formatting helpers ---

    function money(value) {
        const localeTag = { id: 'id-ID', en: 'en-US', ja: 'ja-JP' }[config.locale] || 'id-ID';
        try {
            return new Intl.NumberFormat(localeTag, {
                style: 'currency',
                currency: config.currency,
                maximumFractionDigits: 0,
            }).format(Number(value) || 0);
        } catch {
            return `${config.currency} ${Number(value) || 0}`;
        }
    }

    function priceLine(item) {
        const line = el('p', 'card-price');
        if (item.starting_price === null || item.starting_price === undefined) {
            line.appendChild(el('strong', null, copy.by_quotation));
            return line;
        }
        if (copy.from) line.appendChild(el('span', 'muted', `${copy.from} `));
        line.appendChild(el('strong', null, money(item.starting_price)));
        if (item.price_unit) line.appendChild(el('span', 'muted', ` ${item.price_unit}`));
        return line;
    }

    function statusBadge(status) {
        return el('span', `badge status-${status}`, copy[`status_${status}`] || status);
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
        // images inside cards finish loading after the first layout
        requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
    }

    /**
     * Reveals a reply a few words at a time so it feels like Aya is
     * answering live, even though the backend returns the full text in one
     * response. Resolves once every word is on screen.
     */
    function revealReply(node, text) {
        finishReveal?.(); // an earlier reveal was still running — snap it to done first
        if (reducedMotionQuery.matches) {
            node.innerHTML = formatReply(text);
            return Promise.resolve();
        }

        const words = text.split(/(\s+)/);
        let i = 0;
        return new Promise((resolve) => {
            const finish = () => {
                clearTimeout(revealTimer);
                node.innerHTML = formatReply(text);
                finishReveal = null;
                resolve();
            };
            finishReveal = finish;
            const step = () => {
                i++;
                node.innerHTML = formatReply(words.slice(0, i).join(''));
                scrollToBottom();
                if (i < words.length) {
                    revealTimer = setTimeout(step, 16 + Math.random() * 26);
                } else {
                    finish();
                }
            };
            step();
        });
    }

    // --- message rendering ---

    function row(role, child) {
        const wrap = el('div', `msg msg-${role}`);
        if (role === 'assistant' || role === 'staff') {
            const avatar = el('span', role === 'staff' ? 'msg-avatar msg-avatar-staff' : 'msg-avatar');
            avatar.setAttribute('aria-hidden', 'true');
            if (role === 'staff') avatar.textContent = 'FTS';
            wrap.appendChild(avatar);
        }
        wrap.appendChild(child);
        messagesEl.appendChild(wrap);
        return wrap;
    }

    function textBubble(role, text, { reveal = false } = {}) {
        if (role === 'visitor') {
            panel.classList.add('has-conversation');
            expireConsultationCards();
        }
        const bubble = el('div', 'bubble');
        if (role === 'staff') bubble.appendChild(el('span', 'bubble-label', copy.staff_label));
        let revealed = null;
        if (role === 'assistant' || role === 'staff') {
            const body = el('div');
            if (reveal) {
                revealed = revealReply(body, text);
            } else {
                body.innerHTML = formatReply(text);
            }
            bubble.appendChild(body);
        } else {
            bubble.textContent = text;
        }
        const wrap = row(role, bubble);
        wrap.revealed = revealed;
        return wrap;
    }

    function attachment(nodes) {
        const wrap = el('div', 'msg msg-attachment');
        nodes.forEach((node) => wrap.appendChild(node));
        messagesEl.appendChild(wrap);
    }

    function serviceCard(service) {
        const card = el('article', 'chat-card');
        if (service.is_featured) card.appendChild(el('span', 'badge badge-amber', copy.featured));
        card.appendChild(el('h4', null, service.name));
        card.appendChild(el('p', 'muted', service.summary));
        card.appendChild(priceLine(service));
        const button = el('button', 'card-link', `${copy.view_details} →`);
        button.type = 'button';
        button.addEventListener('click', () => sendMessage(copy.ask_service_msg.replace(':name', service.name)));
        card.appendChild(button);
        return card;
    }

    function serviceDetailCard(service) {
        const card = el('article', 'chat-card chat-card-wide');
        card.appendChild(el('h4', null, service.name));
        card.appendChild(el('p', 'muted', service.summary));

        if (service.features?.length) {
            card.appendChild(el('p', 'card-label', copy.features));
            const list = el('ul', 'check-list');
            service.features.slice(0, 6).forEach((feature) => list.appendChild(el('li', null, feature)));
            card.appendChild(list);
        }

        card.appendChild(el('p', 'card-label', copy.pricing));
        if (service.pricing_tiers?.length) {
            const tiers = el('div', 'tier-grid');
            service.pricing_tiers.forEach((tier) => {
                const tierEl = el('div', 'tier');
                tierEl.appendChild(el('span', 'tier-name', tier.name));
                tierEl.appendChild(el('strong', null, money(tier.price)));
                if (tier.unit) tierEl.appendChild(el('span', 'muted', tier.unit));
                tiers.appendChild(tierEl);
            });
            card.appendChild(tiers);
        } else {
            card.appendChild(priceLine(service));
        }
        if (service.price_note) card.appendChild(el('p', 'card-note', service.price_note));

        if (service.related_projects?.length) {
            const button = el('button', 'card-link', `${copy.see_projects} →`);
            button.type = 'button';
            button.addEventListener('click', () => sendMessage(copy.ask_project_msg.replace(':name', service.related_projects[0].name)));
            card.appendChild(button);
        }
        return card;
    }

    function projectMedia(project) {
        const media = el('div', 'chat-card-media');
        const src = safeUrl(project.image_url);
        if (src) {
            const img = el('img');
            img.src = src;
            img.alt = project.name;
            img.loading = 'lazy';
            img.addEventListener('load', () => {
                if (messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 400) scrollToBottom();
            }, { once: true });
            media.appendChild(img);
        } else {
            media.appendChild(el('span', 'project-placeholder', project.name.slice(0, 1)));
        }
        media.appendChild(statusBadge(project.status));
        return media;
    }

    function visitLink(project) {
        const href = safeUrl(project.live_url);
        if (!href) return null;
        const link = el('a', 'card-link', `${copy.visit} ↗`);
        link.href = href;
        link.target = '_blank';
        link.rel = 'noopener';
        return link;
    }

    function projectCard(project) {
        const card = el('article', 'chat-card chat-card-media-card');
        card.appendChild(projectMedia(project));
        const body = el('div', 'chat-card-body');
        body.appendChild(el('h4', null, project.name));
        body.appendChild(el('p', 'muted', project.summary));
        const button = el('button', 'card-link', `${copy.view_details} →`);
        button.type = 'button';
        button.addEventListener('click', () => sendMessage(copy.ask_project_msg.replace(':name', project.name)));
        body.appendChild(button);
        card.appendChild(body);
        return card;
    }

    function projectDetailCard(project) {
        const card = el('article', 'chat-card chat-card-wide chat-card-media-card');
        card.appendChild(projectMedia(project));
        const body = el('div', 'chat-card-body');
        body.appendChild(el('h4', null, project.name));
        if (project.service?.name) body.appendChild(el('p', 'card-label', project.service.name));
        body.appendChild(el('p', 'muted', project.description || project.summary));
        if (project.highlights?.length) {
            const list = el('ul', 'check-list');
            project.highlights.slice(0, 5).forEach((item) => list.appendChild(el('li', null, item)));
            body.appendChild(list);
        }
        if (project.tech_stack?.length) {
            const tags = el('div', 'tag-row');
            project.tech_stack.forEach((tech) => tags.appendChild(el('span', 'tag', tech)));
            body.appendChild(tags);
        }
        const link = visitLink(project);
        if (link) body.appendChild(link);
        card.appendChild(body);
        return card;
    }

    function leadCard(lead) {
        const card = el('article', 'chat-card chat-card-wide chat-card-success');
        card.appendChild(el('p', 'success-title', `✓ ${copy.lead_received}`));
        const list = el('dl', 'lead-list');
        [
            [copy.reference, lead.reference],
            [copy[`lead_type_${lead.type}`] || lead.type, [lead.name, lead.organization].filter(Boolean).join(' · ')],
            [lead.service ? '' : null, lead.service],
            ['', lead.contact],
        ].forEach(([label, value]) => {
            if (!value || label === null) return;
            const item = el('div');
            if (label) item.appendChild(el('dt', null, label));
            item.appendChild(el('dd', null, value));
            list.appendChild(item);
        });
        card.appendChild(list);
        card.appendChild(el('p', 'muted', copy.lead_followup));
        return card;
    }

    function noticeCard(text) {
        return el('div', 'chat-notice', text);
    }

    function expireConsultationCards() {
        messagesEl.querySelectorAll('[data-consultation-card]').forEach((card) => {
            if (card.dataset.confirmed === 'true') return;
            card.dataset.expired = 'true';
            card.querySelectorAll('button').forEach((button) => { button.disabled = true; });
            card.querySelector('[data-consultation-note]').textContent = copy.consultation_outdated;
        });
    }

    function consultationCard(draft, messageId) {
        expireConsultationCards();
        const card = el('article', 'chat-card chat-card-wide consultation-card');
        card.dataset.consultationCard = messageId;
        card.dataset.confirmed = String(Boolean(draft.confirmed));
        card.appendChild(el('p', 'card-label', copy.consultation_title));
        card.appendChild(el('p', 'consultation-summary', draft.summary));
        const note = el('p', 'muted', draft.confirmed ? copy.consultation_sent : copy.consultation_review);
        note.dataset.consultationNote = '';
        card.appendChild(note);
        const actions = el('div', 'consultation-actions');
        const confirm = el('button', 'btn btn-primary', draft.confirmed ? copy.consultation_sent : copy.consultation_confirm);
        confirm.type = 'button';
        confirm.dataset.consultationConfirm = '';
        confirm.disabled = Boolean(draft.confirmed) || !messageId;
        confirm.addEventListener('click', () => confirmConsultation(card, messageId));
        const edit = el('button', 'btn btn-ghost', copy.consultation_edit);
        edit.type = 'button';
        edit.dataset.consultationEdit = '';
        edit.disabled = Boolean(draft.confirmed) || !messageId;
        edit.addEventListener('click', () => {
            if (busy || handedOver) return;
            inputEl.value = copy.consultation_edit_prompt;
            openPanel();
            inputEl.dispatchEvent(new Event('input'));
            inputEl.setSelectionRange(inputEl.value.length, inputEl.value.length);
        });
        actions.append(confirm, edit);
        card.appendChild(actions);
        return card;
    }

    async function confirmConsultation(card, messageId) {
        if (busy || !ready || handedOver || card.dataset.confirmed === 'true' || card.dataset.expired === 'true') return;
        openPanel();
        stopSpeaking();
        setBusy(true);
        setCharacter('thinking');
        const note = card.querySelector('[data-consultation-note]');
        note.textContent = copy.consultation_sending;
        try {
            const data = await api(config.consultationUrl, { visitor_token: token(), message_id: messageId });
            card.dataset.confirmed = 'true';
            note.textContent = copy.consultation_sent;
            card.querySelector('[data-consultation-confirm]').textContent = copy.consultation_sent;
            renderMessage(data.message);
            setHandedOver(data.status === 'handed_over');
        } catch (error) {
            note.textContent = error.status === 422 || error.status === 404 ? copy.consultation_outdated : copy.consultation_error;
            if (error.status === 422 || error.status === 404) card.dataset.expired = 'true';
        } finally {
            setCharacter(restingState());
            setBusy(false);
            scrollToBottom();
        }
    }

    function renderUiPayload(payloads, messageId) {
        (payloads || []).forEach((payload) => {
            switch (payload.type) {
                case 'service_list': {
                    const scroller = el('div', 'card-scroller');
                    payload.services.forEach((service) => scroller.appendChild(serviceCard(service)));
                    attachment([scroller]);
                    break;
                }
                case 'service_detail':
                    attachment([serviceDetailCard(payload.service)]);
                    break;
                case 'project_list': {
                    const scroller = el('div', 'card-scroller');
                    payload.projects.forEach((project) => scroller.appendChild(projectCard(project)));
                    attachment([scroller]);
                    break;
                }
                case 'project_detail':
                    attachment([projectDetailCard(payload.project)]);
                    break;
                case 'lead_confirmation':
                    attachment([leadCard(payload.lead)]);
                    break;
                case 'consultation_summary':
                    attachment([consultationCard(payload, messageId)]);
                    break;
                case 'handover':
                    attachment([noticeCard(copy.handed_over)]);
                    break;
                case 'waiting_for_staff':
                    attachment([noticeCard(copy.waiting_for_staff)]);
                    break;
                default:
                    break;
            }
        });
    }

    function renderMessage(message, live = false) {
        if (message.id && renderedIds.has(message.id)) return;
        if (message.id) renderedIds.add(message.id);

        // The visitor's own bubble was drawn optimistically before the server
        // gave it an id — claim that bubble instead of drawing it twice.
        if (message.role === 'visitor') {
            const pending = [...messagesEl.querySelectorAll('.msg-visitor[data-pending]')]
                .find((node) => node.textContent === message.content);
            if (pending) {
                delete pending.dataset.pending;
                return;
            }

            textBubble('visitor', message.content);
        } else if (message.role === 'assistant' || message.role === 'system') {
            let revealed = null;
            if (message.content) {
                revealed = textBubble('assistant', message.content, { reveal: live }).revealed;
            }
            renderUiPayload(message.ui_payload, message.id);
            if (live && message.role === 'assistant') reactToReply(message, revealed);
        } else if (message.role === 'staff') {
            textBubble('staff', message.content);
        }
    }

    function renderWelcome() {
        panel.classList.remove('has-conversation');
        const welcome = el('div', 'chat-welcome');
        welcome.appendChild(el('span', 'welcome-mark', '✦'));
        welcome.appendChild(el('span', 'welcome-eyebrow', 'FTS AI COMPANY'));
        welcome.appendChild(el('h2', null, copy.studio_welcome));
        if (copy.company_positioning) welcome.appendChild(el('p', 'welcome-tagline', copy.company_positioning));
        welcome.appendChild(el('p', 'welcome-intro', copy.chat_intro));
        welcome.appendChild(el('p', 'welcome-prompt', copy.hero_try));
        const chips = el('div', 'chat-chips welcome-chips');
        config.quickQuestions.forEach((question) => {
            const chip = el('button', 'chip chip-small', question.label);
            chip.type = 'button';
            chip.dataset.quickMessage = question.message;
            chip.addEventListener('click', () => sendMessage(question.message));
            chips.appendChild(chip);
        });
        welcome.appendChild(chips);
        messagesEl.appendChild(welcome);
    }

    function showTyping() {
        const dots = el('div', 'bubble typing');
        dots.setAttribute('aria-label', copy.thinking);
        dots.append(el('i'), el('i'), el('i'), el('span', 'typing-label', copy.state_thinking));
        typingEl = row('assistant', dots);
        setCharacter('thinking');
        scrollToBottom();
    }

    function hideTyping() {
        typingEl?.remove();
        typingEl = null;
    }

    // --- state ---

    function setBusy(value) {
        busy = value;
        const disabled = busy || !ready || handedOver;
        inputEl.disabled = !ready;
        submitEl.disabled = disabled;
        panel.setAttribute('aria-busy', String(busy));
        document.body.classList.toggle('staff-thinking', busy);
        document.querySelectorAll('[data-quick-message]').forEach((button) => { button.disabled = disabled; });
        panel.querySelectorAll('[data-consultation-card]').forEach((card) => {
            card.querySelectorAll('button').forEach((button) => {
                button.disabled = disabled || card.dataset.confirmed === 'true' || card.dataset.expired === 'true';
            });
        });
    }

    function showBanner(text, tone = 'info') {
        banner.textContent = text;
        banner.dataset.tone = tone;
        banner.hidden = false;
    }

    function hideBanner() {
        banner.hidden = true;
    }

    function setHandedOver(value) {
        const changed = handedOver !== value;
        handedOver = value;
        if (value) {
            showBanner(copy.handed_over, 'info');
            startPolling();
        } else {
            hideBanner();
            stopPolling();
        }
        if (changed && !busy) setCharacter(restingState());
        setBusy(busy);
    }

    function startPolling() {
        if (!pollTimer) pollTimer = setInterval(syncHistory, 4000);
    }

    function stopPolling() {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    function token() {
        try {
            return localStorage.getItem(config.storageKey);
        } catch {
            return window.__aiStaffToken || null;
        }
    }

    function saveToken(value) {
        window.__aiStaffToken = value;
        try {
            if (value) localStorage.setItem(config.storageKey, value);
            else localStorage.removeItem(config.storageKey);
        } catch {
            // private mode — the in-memory token above still works for this page view
        }
    }

    async function api(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body || {}),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(copy.error_generic);
            error.status = response.status;
            throw error;
        }
        return payload;
    }

    async function fetchHistory() {
        const response = await fetch(`${config.historyUrl}?visitor_token=${encodeURIComponent(token())}`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('history_unavailable');
        return response.json();
    }

    async function syncHistory() {
        if (!token()) return;
        try {
            const data = await fetchHistory();
            const before = renderedIds.size;
            data.messages.forEach((message) => renderMessage(message));
            if (renderedIds.size !== before) {
                scrollToBottom();
                // a human team member just replied — the character passes it on
                if (handedOver) setCharacter('talking', 2500);
            }
            if (data.status !== 'handed_over') setHandedOver(false);
        } catch {
            // transient network hiccup — try again on the next tick
        }
    }

    async function sendMessage(text) {
        const message = (text || '').trim();
        if (!message || busy || !ready) return;

        openPanel();

        stopSpeaking();
        document.querySelector('.staff-nudge')?.remove();
        textBubble('visitor', message).dataset.pending = '1';
        showTyping();
        setBusy(true);

        try {
            const data = await api(config.messageUrl, { visitor_token: token(), message, locale: config.locale });
            hideTyping();
            renderMessage(data.message, true);
            setHandedOver(data.status === 'handed_over');
            syncHistory();
        } catch (error) {
            hideTyping();
            setCharacter(restingState());
            if (error.status === 422) {
                saveToken(null);
                await startConversation();
            }
            attachment([noticeCard(copy.error_generic)]);
        } finally {
            setBusy(false);
            scrollToBottom();
        }
    }

    async function startConversation() {
        const data = await api(config.startUrl, { locale: config.locale });
        saveToken(data.visitor_token);
        renderedIds = new Set();
    }

    async function boot() {
        if (!token()) {
            await startConversation();
            renderWelcome();
            return;
        }

        try {
            const data = await fetchHistory();
            renderWelcome();
            data.messages.forEach(renderMessage);
            if (data.status === 'handed_over') setHandedOver(true);
        } catch {
            saveToken(null);
            messagesEl.replaceChildren();
            await startConversation();
            renderWelcome();
        }
    }

    formEl.addEventListener('submit', (event) => {
        event.preventDefault();
        const text = inputEl.value;
        if (!text.trim() || busy || handedOver) return;
        inputEl.value = '';
        inputEl.style.height = '';
        sendMessage(text);
    });

    inputEl.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            formEl.requestSubmit();
        }
    });

    inputEl.addEventListener('input', () => {
        inputEl.style.height = 'auto';
        inputEl.style.height = `${Math.min(inputEl.scrollHeight, 140)}px`;
        if (!busy) setCharacter(inputEl.value.trim() ? 'listening' : restingState());
    });
    inputEl.addEventListener('focus', () => {
        if (mobileQuery.matches && !closingPanel && !panel.classList.contains('is-open')) openPanel();
        if (!busy && !handedOver) setCharacter('listening');
    });
    inputEl.addEventListener('blur', () => { if (!busy) setCharacter(restingState()); });


    panel.querySelector('[data-new-chat]')?.addEventListener('click', async () => {
        if (busy) return;
        ready = false;
        setBusy(true);
        stopSpeaking();
        stopPolling();
        setHandedOver(false);
        messagesEl.replaceChildren();
        saveToken(null);
        try {
            await startConversation();
            renderWelcome();
            ready = true;
        } catch {
            showBanner(copy.connection_error, 'error');
        } finally {
            setBusy(false);
            setCharacter(restingState());
        }
        inputEl.focus();
    });

    document.querySelectorAll('.site-main [data-quick-message]').forEach((button) => {
        button.addEventListener('click', () => sendMessage(button.dataset.quickMessage));
    });

    setBusy(true);
    boot()
        .then(() => {
            ready = true;
        })
        .catch(() => {
            showBanner(copy.connection_error, 'error');
        })
        .finally(() => {
            setBusy(false);
            scrollToBottom();
        });
}

document.addEventListener('DOMContentLoaded', initAiStaff);
