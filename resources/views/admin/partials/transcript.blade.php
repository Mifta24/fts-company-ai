{{-- $messages: the conversation's messages, oldest first --}}
<div class="max-h-[560px] space-y-3 overflow-y-auto px-4 py-4">
    @forelse ($messages as $message)
        @continue($message->role === 'system' && ! $message->content && ! $message->ui_payload)
        <div class="flex {{ $message->role === 'visitor' ? 'justify-end' : 'justify-start' }}">
            <div @class([
                'max-w-[80%] rounded-2xl px-3 py-2 text-sm whitespace-pre-line',
                'bg-sky-600 text-white' => $message->role === 'visitor',
                'bg-stone-100 text-stone-900' => $message->role === 'assistant',
                'bg-sky-50 text-sky-900 border border-sky-200' => $message->role === 'staff',
                'bg-amber-50 text-amber-800 border border-amber-200 text-xs' => $message->role === 'system',
            ])>
                @if ($message->role === 'staff')
                    <p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-500">FTS team</p>
                @elseif ($message->role === 'assistant')
                    <p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wide text-stone-400">AI Staff</p>
                @endif
                {{ $message->content }}
                @if ($message->tool_calls)
                    <p class="mt-1 text-[11px] text-stone-400">Tools: {{ collect($message->tool_calls)->pluck('name')->unique()->implode(', ') }}</p>
                @elseif ($message->role === 'system')
                    Visitor is waiting for the team.
                @endif
            </div>
        </div>
    @empty
        <p class="text-center text-sm text-stone-400">No messages.</p>
    @endforelse
</div>
