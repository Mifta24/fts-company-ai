<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCompany;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    use ResolvesCurrentCompany;

    public function index(Request $request): View
    {
        $company = $this->currentCompany($request);

        $status = in_array($request->query('status'), Lead::STATUSES, true) ? $request->query('status') : null;

        $leads = $company->leads()
            ->with('service')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads.index', compact('company', 'leads', 'status'));
    }

    public function show(Request $request, Lead $lead): View
    {
        $company = $this->currentCompany($request);
        abort_if($lead->company_id !== $company->id, 404);

        $lead->load(['service', 'conversation.messages']);

        return view('admin.leads.show', compact('company', 'lead'));
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($lead->company_id !== $company->id, 404);

        $data = $request->validate(['status' => ['required', Rule::in(Lead::STATUSES)]]);

        $lead->update(['status' => $data['status']]);

        return back()->with('status', "Lead {$lead->reference()} marked as {$data['status']}.");
    }
}
