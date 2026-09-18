<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\AiStaff\AiStaffService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai-staff:chat {--locale=id : id|en|ja}')]
#[Description('Talk to the company AI Staff from the terminal, for testing the tool-calling loop without the web UI.')]
class AiStaffChatCommand extends Command
{
    public function handle(AiStaffService $service): int
    {
        $company = Company::primary();

        if (! $company) {
            $this->error('No published company found. Seed one first: php artisan db:seed');

            return self::FAILURE;
        }

        if (blank(config('services.local_llm.base_url'))) {
            $this->error('LOCAL_LLM_BASE_URL is not set in .env — the AI Staff has no model endpoint to call.');

            return self::FAILURE;
        }

        $locale = $this->option('locale');
        $conversation = $service->startConversation($company, $locale);

        $this->info("Chatting with {$company->ai_staff_name} of {$company->name} ({$locale}). Type 'exit' to quit.");
        $this->newLine();

        while (true) {
            $visitorMessage = $this->ask('You');

            if ($visitorMessage === null || in_array(trim($visitorMessage), ['exit', 'quit'], true)) {
                break;
            }

            $message = $service->reply($company, $conversation, $visitorMessage);

            $this->newLine();
            $this->line("<fg=cyan>{$company->ai_staff_name}:</> ".($message->content ?? '(no text — see UI payload below)'));

            if ($message->tool_calls) {
                $this->line('<fg=gray>[tools] '.collect($message->tool_calls)->pluck('name')->implode(', ').'</>');
            }

            $conversation->refresh();
            if ($conversation->isHandedOver()) {
                $this->warn('Conversation handed over to the human team. Ending session.');
                break;
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
