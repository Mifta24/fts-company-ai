<span @class([
    'rounded-full px-2 py-0.5 text-xs',
    'bg-amber-100 text-amber-700' => $status === 'new',
    'bg-sky-100 text-sky-700' => $status === 'contacted',
    'bg-violet-100 text-violet-700' => $status === 'qualified',
    'bg-emerald-100 text-emerald-700' => $status === 'won',
    'bg-stone-100 text-stone-500' => $status === 'lost',
])>{{ ucfirst($status) }}</span>
