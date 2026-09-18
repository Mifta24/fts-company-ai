@php
    $staffConfig = [
        'startUrl' => route('ai-staff.start'),
        'messageUrl' => route('ai-staff.message'),
        'consultationUrl' => route('ai-staff.consultation'),
        'historyUrl' => route('ai-staff.history'),
        'storageKey' => 'ai_staff_token_'.$company->slug,
        'locale' => $locale,
        'currency' => $company->currency,
        'staffName' => $staffName,
        'quickQuestions' => array_slice($quickQuestions, 0, 4),
        'copy' => collect($copy)->only([
            'chat_intro', 'thinking', 'handed_over', 'waiting_for_staff', 'staff_label', 'view_details',
            'see_projects', 'lead_received', 'lead_followup', 'lead_type_consultation', 'lead_type_demo',
            'lead_type_quotation', 'reference', 'features', 'pricing', 'from', 'by_quotation', 'visit',
            'ask_about_project', 'ask_project_msg', 'ask_service_msg', 'status_live', 'status_pilot',
            'status_demo', 'status_in_development', 'connection_error', 'error_generic', 'chat_send',
            'featured', 'hero_try', 'studio_welcome', 'voice_on', 'voice_off', 'voice_unsupported', 'nudge_general', 'nudge_services',
            'nudge_projects', 'nudge_pricing', 'nudge_about', 'nudge_contact', 'talk_to_staff',
            'state_idle', 'state_listening', 'state_thinking', 'state_talking', 'state_happy', 'state_handover',
            'consultation_title', 'consultation_review', 'consultation_confirm', 'consultation_edit',
            'consultation_edit_prompt', 'consultation_sent', 'consultation_sending', 'consultation_error',
            'consultation_outdated',
        ])->map(fn ($text, $key) => in_array($key, ['chat_intro', 'thinking', 'studio_welcome'], true) ? str_replace(':name', $staffName, $text) : $text)->all(),
    ];
@endphp
<aside id="ai-staff" class="staff-panel" data-staff-panel aria-label="{{ $staffName }} · {{ $copy['staff_role'] }}" tabindex="-1">
    <script type="application/json" data-staff-config>@json($staffConfig)</script>

    <div class="staff-head">
        <x-staff-avatar :size="42" />
        <div class="staff-head-text">
            <p class="staff-name">{{ $staffName }} <span class="online-dot" aria-hidden="true"></span></p>
            <p class="staff-role">{{ $copy['staff_role'] }} · {{ $copy['staff_online'] }}</p>
        </div>
        <button type="button" class="icon-btn voice-btn" data-voice-toggle aria-pressed="false" title="{{ $copy['voice_off'] }}" aria-label="{{ $copy['voice_off'] }}">
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8v4h3l4 3V5L6 8H3z"/><path class="voice-wave" d="M13 7.5a3.5 3.5 0 0 1 0 5M15.5 5.5a6.5 6.5 0 0 1 0 9"/></svg>
        </button>
        <button type="button" class="icon-btn" data-new-chat title="{{ $copy['new_chat'] }}" aria-label="{{ $copy['new_chat'] }}">
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M16 10a6 6 0 1 1-1.8-4.3"/><path d="M16 3.5V7h-3.5"/></svg>
        </button>
        <button type="button" class="icon-btn staff-close" data-close-staff aria-label="{{ $copy['close'] }}">
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
        </button>
        <button type="button" class="icon-btn staff-expand" data-open-staff aria-label="{{ $copy['studio_expand'] }}">
            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3h5v5M17 3l-5 5M8 17H3v-5M3 17l5-5"/></svg>
        </button>
    </div>

    <div class="studio-chat-label"><span class="live-dot" aria-hidden="true"></span><span data-character-status role="status" aria-live="polite">{{ $copy['state_idle'] }}</span><span>FTS AI</span></div>

    <div data-messages class="staff-messages" role="log" aria-live="polite" aria-label="{{ $staffName }}"></div>

    <div data-status-banner class="staff-banner" hidden></div>

    <form data-chat-form class="staff-form">
        <label for="staff-input" class="sr-only">{{ $copy['chat_placeholder'] }}</label>
        <textarea id="staff-input" data-chat-input rows="1" maxlength="2000" placeholder="{{ $copy['chat_placeholder'] }}" autocomplete="off"></textarea>
        <button type="submit" data-chat-submit class="send-btn" aria-label="{{ $copy['chat_send'] }}">
            <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor"><path d="M3.4 2.6a.8.8 0 0 1 .9-.1l13 6.8a.8.8 0 0 1 0 1.4l-13 6.8a.8.8 0 0 1-1.1-1l2.1-5.7h5.4a.8.8 0 0 0 0-1.6H5.3L3.2 3.5a.8.8 0 0 1 .2-.9z"/></svg>
        </button>
    </form>
    <p class="staff-disclosure">{{ $copy['ai_disclosure'] }}</p>
</aside>
