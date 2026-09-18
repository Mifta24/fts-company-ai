<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCompany;
use App\Http\Controllers\Controller;
use App\Models\HandoverRequest;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesCurrentCompany;

    public function index(Request $request): View
    {
        $company = $this->currentCompany($request);

        $openHandoverQuery = HandoverRequest::whereHas('conversation', fn ($q) => $q->where('company_id', $company->id))
            ->where('status', HandoverRequest::STATUS_OPEN);

        $stats = [
            'conversations_7d' => $company->conversations()->where('created_at', '>=', now()->subDays(7))->has('messages', '>', 0)->count(),
            'new_leads' => $company->leads()->where('status', Lead::STATUS_NEW)->count(),
            'open_handovers' => (clone $openHandoverQuery)->count(),
            'knowledge_items' => $company->knowledgeItems()->where('is_active', true)->count(),
        ];

        return view('admin.dashboard', [
            'company' => $company,
            'stats' => $stats,
            'recentLeads' => $company->leads()->latest()->take(5)->with('service')->get(),
            'openHandovers' => $openHandoverQuery->latest()->take(5)->with('conversation')->get(),
            'recentConversations' => $company->conversations()->has('messages', '>', 1)->latest('last_message_at')->take(5)
                ->withCount('messages')->get(),
        ]);
    }
}
