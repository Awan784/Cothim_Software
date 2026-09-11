<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Investor;
use App\Models\NominalAccount;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AccountingAssistantService
{
    public function __construct(
        private AccountingAssistantExecutor $executor,
        private PartyLedgerService $partyLedger,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    public function chat(User $user, string $message, array $history = []): array
    {
        $message = trim($message);

        if ($message === '') {
            return ['reply' => 'Please type a command, for example: receive 50000 cash from customer Ali.', 'pending' => session('assistant.pending')];
        }

        if ($this->isConfirm($message) && session('assistant.pending')) {
            return $this->confirm($user);
        }

        if ($this->isCancel($message) && session('assistant.pending')) {
            session()->forget('assistant.pending');

            return ['reply' => 'Cancelled. Nothing was saved.', 'pending' => null];
        }

        $local = $this->handleLocalCommand($message);
        if ($local !== null) {
            return $local;
        }

        $provider = $this->resolveProvider();
        if ($provider === null) {
            return [
                'reply' => "I can draft entries without AI using commands like:\n• receive 50000 cash from customer Ali\n• add customer Ahmed\n• balances\n\nFor free-text chat, set GROQ_API_KEY in .env.",
                'pending' => session('assistant.pending'),
            ];
        }

        try {
            return match ($provider) {
                'ollama' => $this->chatWithOpenAiCompatible('ollama', $message, $history),
                'groq' => $this->chatWithOpenAiCompatible('groq', $message, $history),
                'gemini' => $this->chatWithGemini($message, $history),
                default => $this->chatWithAnthropic($message, $history),
            };
        } catch (Throwable $e) {
            if ($provider === 'groq' && filled(config('services.gemini.key'))) {
                try {
                    return $this->chatWithGemini($message, $history);
                } catch (Throwable $fallback) {
                    report($fallback);
                }
            }

            report($e);

            return [
                'reply' => $this->userFacingAiError($message, $e),
                'pending' => session('assistant.pending'),
            ];
        }
    }

    private function resolveProvider(): ?string
    {
        $preferred = strtolower((string) config('services.assistant.provider', 'groq'));
        $hasGroq = filled(config('services.groq.key'));
        $hasGemini = filled(config('services.gemini.key'));
        $hasAnthropic = filled(config('services.anthropic.key'));

        if ($preferred === 'groq' && $hasGroq) {
            return 'groq';
        }
        if ($preferred === 'gemini' && $hasGemini) {
            return 'gemini';
        }
        if ($preferred === 'anthropic' && $hasAnthropic) {
            return 'anthropic';
        }
        if ($preferred === 'ollama' && $this->ollamaIsReachable()) {
            return 'ollama';
        }

        if ($hasGroq) {
            return 'groq';
        }
        if ($hasGemini) {
            return 'gemini';
        }
        if ($hasAnthropic) {
            return 'anthropic';
        }
        if ($this->ollamaIsReachable()) {
            return 'ollama';
        }

        return null;
    }

    private function ollamaIsReachable(): bool
    {
        try {
            $url = (string) config('services.ollama.base_url', 'http://127.0.0.1:11434/v1/chat/completions');
            $root = preg_replace('#/v1/chat/completions$#', '', $url) ?: 'http://127.0.0.1:11434';

            return Http::timeout(1)->get($root.'/api/tags')->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    public function confirm(User $user): array
    {
        $pending = session('assistant.pending');
        if (! is_array($pending)) {
            return ['reply' => 'There is nothing waiting to confirm.', 'pending' => null];
        }

        try {
            return $this->executor->execute($user, $pending);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return ['reply' => $e->getMessage(), 'pending' => $pending];
        }
    }

    /**
     * @return array{reply: string, pending: null}
     */
    public function cancel(): array
    {
        session()->forget('assistant.pending');

        return ['reply' => 'Cancelled. Nothing was saved.', 'pending' => null];
    }

    /**
     * @return array{reply: string, pending: ?array<string, mixed>}|null
     */
    private function handleLocalCommand(string $message): ?array
    {
        $normalized = preg_replace('/\s+/', ' ', strtolower($message)) ?? $message;

        if (preg_match('/^(balances?|totals?|summary)$/i', $normalized)) {
            return ['reply' => $this->toolGetSummary([]), 'pending' => session('assistant.pending')];
        }

        $ledgerAsk = $this->parseLedgerCommand($message);
        if ($ledgerAsk !== null) {
            return [
                'reply' => $this->formatLedgerForUser($this->lookupPartyLedger($ledgerAsk), $this->detectReplyScript($message)),
                'pending' => session('assistant.pending'),
            ];
        }

        if (preg_match('/^(?:add|create|new)\s+(customer|supplier|investor|bank|expense|stock category)\s+(.+)$/i', $message, $m)) {
            $label = strtolower($m[1]);
            $name = trim($m[2], " \t\"'");
            $kind = match ($label) {
                'customer' => 'customer',
                'supplier' => 'supplier',
                'investor' => 'investor',
                'bank' => 'bank_account',
                'expense' => 'expense_account',
                default => 'stock_category',
            };

            return $this->setPending($kind, ['name' => $name, 'opening_balance' => 0, 'opening_investment' => 0], 'Create '.$label.' "'.$name.'".');
        }

        if (preg_match('/^stock\s+(in|out|adjust)\s+(\d[\d,]*(?:\.\d+)?)\s+(?:of\s+)?(.+)$/i', $message, $m)) {
            return $this->draftStockMovement([
                'type' => strtolower($m[1]),
                'quantity' => (float) str_replace(',', '', $m[2]),
                'item_name' => trim($m[3]),
            ]);
        }

        $voucher = $this->parseVoucherCommand($message);
        if ($voucher !== null) {
            return $this->draftCashVoucher($voucher);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseVoucherCommand(string $message): ?array
    {
        $text = trim($message);
        if (! preg_match('/^(receive|received|receipt|pay|paid|payment)\b/i', $text, $typeMatch)) {
            return null;
        }

        $type = str_starts_with(strtolower($typeMatch[1]), 'p') ? 'payment' : 'receive';

        if (! preg_match('/(\d[\d,]*(?:\.\d+)?)/', $text, $amountMatch)) {
            return null;
        }
        $amount = (float) str_replace(',', '', $amountMatch[1]);
        if ($amount <= 0) {
            return null;
        }

        $paymentMethod = 'cash';
        $bankName = null;
        if (preg_match('/\bbank(?:\s+account)?(?:\s+(.+?))?(?=\s+(?:to|from|for|customer|supplier|investor|expense|other)\b|$)/i', $text, $bankMatch)) {
            $paymentMethod = 'bank';
            $bankName = trim($bankMatch[1] ?? '');
            if ($bankName === '' || preg_match('/^(to|from|for)$/i', $bankName)) {
                $bankName = null;
            }
        } elseif (preg_match('/\bcash\b/i', $text)) {
            $paymentMethod = 'cash';
        }

        $accountType = 'other';
        $partyName = null;
        if (preg_match('/\b(customer|supplier|investor|expense|other)\s+(.+)$/i', $text, $partyMatch)) {
            $accountType = strtolower($partyMatch[1]);
            $partyName = trim($partyMatch[2], " \t\"'");
        } elseif (preg_match('/\b(?:from|to|for)\s+(.+?)(?:\s+(?:in|via|by)\s+(?:cash|bank).*)?$/i', $text, $fromMatch)) {
            $partyName = trim($fromMatch[1], " \t\"'");
            $partyName = preg_replace('/\s+(cash|bank)\b.*$/i', '', $partyName) ?? $partyName;
        }

        if (! $partyName) {
            return null;
        }

        $notes = null;
        if (preg_match('/\bnotes?\s*[:=]\s*(.+)$/i', $text, $notesMatch)) {
            $notes = trim($notesMatch[1]);
        }

        return [
            'type' => $type,
            'payment_method' => $paymentMethod,
            'bank_name' => $bankName,
            'account_type' => $accountType,
            'party_name' => $partyName,
            'amount' => $amount,
            'voucher_date' => now()->toDateString(),
            'notes' => $notes,
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function chatWithOpenAiCompatible(string $provider, string $message, array $history): array
    {
        $messages = [['role' => 'system', 'content' => $this->systemPrompt($message)]];
        foreach ($this->plainHistory($history) as $item) {
            $messages[] = $item;
        }
        $messages[] = ['role' => 'user', 'content' => $this->messageWithLanguageHint($message)];

        $tools = $this->openAiTools();
        $model = (string) config('services.'.$provider.'.model');
        $url = (string) config('services.'.$provider.'.base_url');
        $maxTokens = (int) config('services.'.$provider.'.max_tokens', 1200);
        $token = $provider === 'groq'
            ? (string) config('services.groq.key')
            : 'ollama';

        for ($i = 0; $i < 6; $i++) {
            $payload = [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => 0.1,
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
            ];

            if ($provider === 'groq' && str_contains($model, 'gpt-oss')) {
                $payload['include_reasoning'] = false;
                $payload['reasoning_effort'] = 'low';
            }

            $response = Http::withToken($token)
                ->timeout($provider === 'ollama' ? 180 : 60)
                ->post($url, $payload);

            if (! $response->successful()) {
                $error = (string) data_get($response->json(), 'error.message', $response->body());
                $code = (string) data_get($response->json(), 'error.code', '');
                $status = $response->status();
                if ($provider === 'groq' && $code === 'model_not_found' && $model !== 'openai/gpt-oss-20b') {
                    $model = 'openai/gpt-oss-20b';
                    $i--;
                    continue;
                }
                if ($provider === 'groq' && ($status === 429 || str_contains(strtolower($error), 'rate limit')) && $model !== 'qwen/qwen3.6-27b') {
                    $model = 'qwen/qwen3.6-27b';
                    $i--;
                    continue;
                }
                throw new RuntimeException(ucfirst($provider).' HTTP '.$status.': '.$error);
            }

            $choice = $response->json('choices.0.message') ?? [];
            $toolCalls = is_array($choice['tool_calls'] ?? null) ? $choice['tool_calls'] : [];
            $messages[] = $this->assistantMessageForHistory($choice);

            if ($toolCalls === []) {
                return [
                    'reply' => $this->finalAssistantReply($choice),
                    'pending' => session('assistant.pending'),
                ];
            }

            foreach ($toolCalls as $call) {
                $name = (string) data_get($call, 'function.name');
                $args = $this->decodeToolArguments(data_get($call, 'function.arguments', '{}'));
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? Str::uuid()->toString(),
                    'name' => $name,
                    'content' => $this->runTool($name, $args),
                ];
            }
        }

        return [
            'reply' => 'I needed too many steps for that request. Please try a shorter command.',
            'pending' => session('assistant.pending'),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function chatWithGemini(string $message, array $history): array
    {
        $contents = [];
        foreach ($this->plainHistory($history) as $item) {
            $contents[] = [
                'role' => $item['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $item['content']]],
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $this->messageWithLanguageHint($message)]]];

        $model = (string) config('services.gemini.model', 'gemini-3.6-flash');
        $key = (string) config('services.gemini.key');
        $tools = [['functionDeclarations' => $this->geminiTools()]];

        for ($i = 0; $i < 6; $i++) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.urlencode($key);
            $response = Http::timeout(60)->post($url, [
                'system_instruction' => ['parts' => [['text' => $this->systemPrompt($message)]]],
                'contents' => $contents,
                'tools' => $tools,
                'generationConfig' => [
                    'maxOutputTokens' => (int) config('services.gemini.max_tokens', 1200),
                ],
            ]);

            if (! $response->successful()) {
                $error = (string) data_get($response->json(), 'error.message', $response->body());
                if ($response->status() === 404 && $model !== 'gemini-3.6-flash') {
                    $model = 'gemini-3.6-flash';
                    $i--;
                    continue;
                }
                throw new RuntimeException('Gemini HTTP '.$response->status().': '.$error);
            }

            $parts = $response->json('candidates.0.content.parts') ?? [];
            $contents[] = ['role' => 'model', 'parts' => $parts];

            $functionCalls = [];
            $textParts = [];
            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $functionCalls[] = $part['functionCall'];
                }
                if (isset($part['text'])) {
                    $textParts[] = $part['text'];
                }
            }

            if ($functionCalls === []) {
                return [
                    'reply' => trim(implode("\n", $textParts)) ?: 'Draft is ready. Confirm to save.',
                    'pending' => session('assistant.pending'),
                ];
            }

            $responseParts = [];
            foreach ($functionCalls as $call) {
                $name = (string) ($call['name'] ?? '');
                $args = is_array($call['args'] ?? null) ? $call['args'] : [];
                $result = $this->runTool($name, $args);
                $decoded = json_decode($result, true);
                $responseParts[] = [
                    'functionResponse' => [
                        'name' => $name,
                        'response' => is_array($decoded) ? $decoded : ['result' => $result],
                    ],
                ];
            }
            $contents[] = ['role' => 'user', 'parts' => $responseParts];
        }

        return [
            'reply' => 'I needed too many steps for that request. Please try a shorter command.',
            'pending' => session('assistant.pending'),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function chatWithAnthropic(string $message, array $history): array
    {
        $messages = [];
        foreach ($this->plainHistory($history) as $item) {
            $messages[] = $item;
        }
        $messages[] = ['role' => 'user', 'content' => $this->messageWithLanguageHint($message)];

        $tools = $this->tools();

        for ($i = 0; $i < 6; $i++) {
            $response = Http::withHeaders([
                'x-api-key' => (string) config('services.anthropic.key'),
                'anthropic-version' => (string) config('services.anthropic.version', '2023-06-01'),
                'content-type' => 'application/json',
            ])->timeout(60)->post((string) config('services.anthropic.base_url'), [
                'model' => config('services.anthropic.model'),
                'max_tokens' => (int) config('services.anthropic.max_tokens', 1200),
                'system' => $this->systemPrompt($message),
                'tools' => $tools,
                'messages' => $messages,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Anthropic HTTP '.$response->status().': '.$response->body());
            }

            $data = $response->json();
            $stop = $data['stop_reason'] ?? '';
            $content = $data['content'] ?? [];
            $messages[] = ['role' => 'assistant', 'content' => $content];

            if ($stop !== 'tool_use') {
                return [
                    'reply' => $this->textFromContent($content),
                    'pending' => session('assistant.pending'),
                ];
            }

            $toolResults = [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') !== 'tool_use') {
                    continue;
                }

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content' => $this->runTool((string) $block['name'], is_array($block['input'] ?? null) ? $block['input'] : []),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        return [
            'reply' => 'I needed too many steps for that request. Please try a shorter command.',
            'pending' => session('assistant.pending'),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return list<array{role: string, content: string}>
     */
    private function plainHistory(array $history): array
    {
        $messages = [];
        foreach (array_slice($history, -4) as $item) {
            $role = $item['role'] ?? '';
            $content = trim((string) ($item['content'] ?? ''));
            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }
            if (mb_strlen($content) > 280) {
                $content = mb_substr($content, 0, 280).'…';
            }
            $messages[] = ['role' => $role, 'content' => $content];
        }

        return $messages;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function openAiTools(): array
    {
        $converted = [];
        foreach ($this->tools() as $tool) {
            $converted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['input_schema'],
                ],
            ];
        }

        return $converted;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function geminiTools(): array
    {
        $converted = [];
        foreach ($this->tools() as $tool) {
            $schema = $tool['input_schema'];
            if (($schema['properties'] ?? null) instanceof \stdClass) {
                $schema['properties'] = new \stdClass;
            }
            $converted[] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => $schema,
            ];
        }

        return $converted;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function runTool(string $name, array $input): string
    {
        return match ($name) {
            'search_accounts' => $this->toolSearchAccounts($input),
            'get_summary' => $this->toolGetSummary($input),
            'get_party_ledger' => $this->toolGetPartyLedger($input),
            'draft_cash_voucher' => $this->encodeDraftResult($this->draftCashVoucher($input)),
            'draft_record' => $this->encodeDraftResult($this->draftRecord($input)),
            'draft_stock_item' => $this->encodeDraftResult($this->draftStockItem($input)),
            'draft_stock_movement' => $this->encodeDraftResult($this->draftStockMovement($input)),
            'draft_purchase_order' => $this->encodeDraftResult($this->draftPurchaseOrder($input)),
            'draft_journal_voucher' => $this->encodeDraftResult($this->draftJournalVoucher($input)),
            default => json_encode(['error' => 'Unknown tool']),
        };
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolSearchAccounts(array $input): string
    {
        $query = trim((string) ($input['query'] ?? ''));
        $type = strtolower(trim((string) ($input['type'] ?? 'all')));
        if ($query === '') {
            return json_encode(['error' => 'query is required']);
        }

        $types = $type === 'all' || $type === ''
            ? ['customer', 'supplier', 'investor', 'expense', 'bank', 'stock', 'stock_category', 'nominal']
            : [$type];

        $matches = [];
        foreach ($types as $accountType) {
            foreach ($this->searchByType($accountType, $query) as $row) {
                $matches[] = $row;
            }
        }

        return json_encode([
            'query' => $query,
            'count' => count($matches),
            'matches' => array_slice($matches, 0, 15),
        ], JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * @param  array<string, mixed>  $_input
     */
    private function toolGetSummary(array $_input): string
    {
        $cash = (float) CashAccount::query()->orderByDesc('is_active')->orderBy('id')->value('current_balance');
        $bank = (float) BankAccount::sum('current_balance');
        $receivable = (float) Customer::sum('current_balance');
        $payable = (float) Supplier::sum('current_balance');
        $bankIn = (float) CashVoucher::query()->where('type', 'receive')->where('payment_method', 'bank')->sum('amount');
        $bankOut = (float) CashVoucher::query()->where('type', 'payment')->where('payment_method', 'bank')->sum('amount');
        $cashIn = (float) CashVoucher::query()->where('type', 'receive')->where('payment_method', 'cash')->sum('amount');

        return "Cash balance: ".number_format($cash, 2)
            ."\nBank balance: ".number_format($bank, 2)
            ."\nCustomer receivable: ".number_format($receivable, 2)
            ."\nSupplier payable: ".number_format($payable, 2)
            ."\nTotal cash received: ".number_format($cashIn, 2)
            ."\nTotal received in bank: ".number_format($bankIn, 2)
            ."\nTotal paid from bank: ".number_format($bankOut, 2);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function draftCashVoucher(array $input): array
    {
        $type = strtolower((string) ($input['type'] ?? ''));
        $type = in_array($type, ['receive', 'payment'], true) ? $type : null;
        $paymentMethod = strtolower((string) ($input['payment_method'] ?? 'cash'));
        $paymentMethod = in_array($paymentMethod, ['cash', 'bank'], true) ? $paymentMethod : 'cash';
        $amount = (float) ($input['amount'] ?? 0);
        $accountType = strtolower((string) ($input['account_type'] ?? 'other'));
        if (! in_array($accountType, ['customer', 'supplier', 'investor', 'expense', 'other'], true)) {
            $accountType = 'other';
        }

        if (! $type || $amount <= 0) {
            return ['reply' => 'I need a voucher type (receive or payment) and an amount greater than 0.', 'pending' => session('assistant.pending')];
        }

        $partyName = trim((string) ($input['party_name'] ?? $input['other_name'] ?? ''));
        $accountId = isset($input['account_id']) ? (int) $input['account_id'] : null;
        $otherName = null;

        if ($accountType === 'other') {
            $otherName = $partyName !== '' ? $partyName : trim((string) ($input['other_name'] ?? ''));
            if ($otherName === '') {
                return ['reply' => 'Please give a name for this Other account.', 'pending' => session('assistant.pending')];
            }
        } else {
            $resolved = $this->resolveParty($accountType, $partyName, $accountId);
            if (isset($resolved['error'])) {
                return ['reply' => $resolved['error'], 'pending' => session('assistant.pending')];
            }
            $accountId = $resolved['id'];
            $partyName = $resolved['name'];
        }

        $bankAccountId = null;
        $bankLabel = 'Cash';
        if ($paymentMethod === 'bank') {
            $bank = $this->resolveBank($input['bank_name'] ?? $input['bank_account_id'] ?? null);
            if (isset($bank['error'])) {
                return ['reply' => $bank['error'], 'pending' => session('assistant.pending')];
            }
            $bankAccountId = $bank['id'];
            $bankLabel = 'Bank — '.$bank['name'];
        }

        $date = (string) ($input['voucher_date'] ?? now()->toDateString());
        $payload = [
            'type' => $type,
            'payment_method' => $paymentMethod,
            'bank_account_id' => $bankAccountId,
            'account_type' => $accountType,
            'account_id' => $accountId,
            'other_name' => $otherName,
            'amount' => $amount,
            'voucher_date' => $date,
            'reference' => $input['reference'] ?? null,
            'notes' => $input['notes'] ?? 'Created via assistant',
        ];

        $summary = ucfirst($type).' '.number_format($amount, 2).' via '.$bankLabel
            .' for '.ucfirst($accountType).' '.($otherName ?: $partyName)
            .' on '.$date.'.';

        return $this->setPending('cash_voucher', $payload, $summary);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function draftRecord(array $input): array
    {
        $type = strtolower((string) ($input['record_type'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            return ['reply' => 'Name is required.', 'pending' => session('assistant.pending')];
        }

        $kind = match ($type) {
            'customer' => 'customer',
            'supplier' => 'supplier',
            'investor' => 'investor',
            'bank', 'bank_account' => 'bank_account',
            'expense', 'expense_account' => 'expense_account',
            'stock_category', 'category' => 'stock_category',
            'nominal', 'nominal_account' => 'nominal_account',
            default => null,
        };

        if ($kind === null) {
            return ['reply' => 'Unknown record type. Use customer, supplier, investor, bank_account, expense_account, stock_category, or nominal_account.', 'pending' => session('assistant.pending')];
        }

        $payload = [
            'name' => $name,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'address' => $input['address'] ?? null,
            'opening_balance' => (float) ($input['opening_balance'] ?? 0),
            'opening_investment' => (float) ($input['opening_investment'] ?? $input['opening_balance'] ?? 0),
            'bank_name' => $input['bank_name'] ?? null,
            'account_number' => $input['account_number'] ?? null,
            'description' => $input['description'] ?? null,
            'code' => $input['code'] ?? null,
            'type' => $input['nominal_type'] ?? $input['type'] ?? 'asset',
        ];

        return $this->setPending($kind, $payload, 'Create '.str_replace('_', ' ', $kind).' "'.$name.'".');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function draftStockItem(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $categoryName = trim((string) ($input['category_name'] ?? ''));
        if ($name === '' || $categoryName === '') {
            return ['reply' => 'Stock item needs a name and category_name.', 'pending' => session('assistant.pending')];
        }

        $resolved = $this->resolveNamed(StockCategory::query(), $categoryName, 'stock category');
        if (isset($resolved['error'])) {
            return ['reply' => $resolved['error'], 'pending' => session('assistant.pending')];
        }

        $payload = [
            'name' => $name,
            'stock_category_id' => $resolved['id'],
            'sku' => $input['sku'] ?? null,
            'unit' => $input['unit'] ?? null,
            'cost_price' => (float) ($input['cost_price'] ?? 0),
            'sale_price' => (float) ($input['sale_price'] ?? 0),
            'reorder_level' => (int) ($input['reorder_level'] ?? 0),
        ];

        return $this->setPending('stock_item', $payload, 'Create stock item "'.$name.'" in category '.$resolved['name'].' cost '.number_format((float) $payload['cost_price'], 2).'.');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function draftStockMovement(array $input): array
    {
        $type = strtolower((string) ($input['type'] ?? ''));
        if (! in_array($type, ['in', 'out', 'adjust'], true)) {
            return ['reply' => 'Stock movement type must be in, out, or adjust.', 'pending' => session('assistant.pending')];
        }
        $qty = (float) ($input['quantity'] ?? 0);
        if ($qty <= 0) {
            return ['reply' => 'Quantity must be greater than 0.', 'pending' => session('assistant.pending')];
        }

        $itemName = trim((string) ($input['item_name'] ?? ''));
        $resolved = $this->resolveNamed(StockItem::query(), $itemName, 'stock item', isset($input['stock_item_id']) ? (int) $input['stock_item_id'] : null);
        if (isset($resolved['error'])) {
            return ['reply' => $resolved['error'], 'pending' => session('assistant.pending')];
        }

        $payload = [
            'stock_item_id' => $resolved['id'],
            'type' => $type,
            'quantity' => $qty,
            'unit_cost' => $input['unit_cost'] ?? null,
            'moved_at' => $input['moved_at'] ?? now()->toDateTimeString(),
            'reference' => $input['reference'] ?? null,
            'notes' => $input['notes'] ?? 'Created via assistant',
        ];

        return $this->setPending('stock_movement', $payload, 'Stock '.$type.' '.number_format($qty, 2).' of '.$resolved['name'].'.');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function draftPurchaseOrder(array $input): array
    {
        $supplierName = trim((string) ($input['supplier_name'] ?? ''));
        $supplier = $this->resolveParty('supplier', $supplierName, isset($input['supplier_id']) ? (int) $input['supplier_id'] : null);
        if (isset($supplier['error'])) {
            return ['reply' => $supplier['error'], 'pending' => session('assistant.pending')];
        }

        $rows = $input['items'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return ['reply' => 'Add at least one purchase order item.', 'pending' => session('assistant.pending')];
        }

        $items = [];
        $summaryParts = [];
        foreach ($rows as $row) {
            $itemName = trim((string) ($row['item_name'] ?? ''));
            $stockId = null;
            if (! empty($row['stock_item_id']) || $itemName !== '') {
                $stock = $this->resolveNamed(StockItem::query(), $itemName, 'stock item', isset($row['stock_item_id']) ? (int) $row['stock_item_id'] : null);
                if (! isset($stock['error'])) {
                    $stockId = $stock['id'];
                    $itemName = $stock['name'];
                }
            }
            if ($itemName === '') {
                return ['reply' => 'Each PO line needs an item name.', 'pending' => session('assistant.pending')];
            }
            $qty = (float) ($row['quantity'] ?? 0);
            $price = (float) ($row['unit_price'] ?? 0);
            if ($qty <= 0) {
                return ['reply' => 'Each PO line needs a quantity greater than 0.', 'pending' => session('assistant.pending')];
            }
            $items[] = [
                'stock_item_id' => $stockId,
                'item_name' => $itemName,
                'unit' => $row['unit'] ?? null,
                'unit_price' => $price,
                'quantity' => $qty,
                'note' => $row['note'] ?? null,
            ];
            $summaryParts[] = $qty.' x '.$itemName.' @ '.number_format($price, 2);
        }

        $payload = [
            'supplier_id' => $supplier['id'],
            'po_date' => $input['po_date'] ?? now()->toDateString(),
            'notes' => $input['notes'] ?? 'Created via assistant',
            'items' => $items,
        ];

        return $this->setPending('purchase_order', $payload, 'Create PO for '.$supplier['name'].': '.implode(', ', $summaryParts).'.');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    private function draftJournalVoucher(array $input): array
    {
        $rows = $input['lines'] ?? [];
        if (! is_array($rows) || count($rows) < 2) {
            return ['reply' => 'Journal voucher needs at least two lines (debit and credit).', 'pending' => session('assistant.pending')];
        }

        $lines = [];
        $parts = [];
        foreach ($rows as $row) {
            $accountType = strtolower((string) ($row['account_type'] ?? ''));
            if (! in_array($accountType, ['customer', 'supplier', 'investor', 'expense', 'bank', 'cash', 'nominal'], true)) {
                return ['reply' => 'Each journal line needs account_type: customer, supplier, investor, expense, bank, cash, or nominal.', 'pending' => session('assistant.pending')];
            }
            $resolved = $this->resolveJournalAccount($accountType, (string) ($row['account_name'] ?? ''), isset($row['account_id']) ? (int) $row['account_id'] : null);
            if (isset($resolved['error'])) {
                return ['reply' => $resolved['error'], 'pending' => session('assistant.pending')];
            }
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);
            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                return ['reply' => 'Each journal line must have either debit or credit, not both.', 'pending' => session('assistant.pending')];
            }
            $lines[] = [
                'account_type' => $accountType,
                'account_id' => $resolved['id'],
                'debit' => $debit,
                'credit' => $credit,
                'line_note' => $row['line_note'] ?? null,
            ];
            $parts[] = $resolved['name'].' '.($debit > 0 ? 'Dr '.number_format($debit, 2) : 'Cr '.number_format($credit, 2));
        }

        $payload = [
            'voucher_date' => $input['voucher_date'] ?? now()->toDateString(),
            'notes' => $input['notes'] ?? 'Created via assistant',
            'lines' => $lines,
        ];

        return $this->setPending('journal_voucher', $payload, 'Create journal: '.implode('; ', $parts).'.');
    }

    /**
     * @return array{id: int, name: string}|array{error: string}
     */
    private function resolveNamed($query, string $name, string $label, ?int $id = null): array
    {
        if ($id) {
            $row = $query->whereKey($id)->first(['id', 'name']);
            if (! $row) {
                return ['error' => 'No '.$label.' found with ID '.$id.'.'];
            }

            return ['id' => (int) $row->id, 'name' => (string) $row->name];
        }

        $name = trim($name);
        if ($name === '') {
            return ['error' => 'Please give a '.$label.' name.'];
        }

        $exact = (clone $query)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first(['id', 'name']);
        if ($exact) {
            return ['id' => (int) $exact->id, 'name' => (string) $exact->name];
        }

        $matches = (clone $query)->where('name', 'like', '%'.$name.'%')->orderBy('name')->limit(8)->get(['id', 'name']);
        if ($matches->count() === 1) {
            $row = $matches->first();

            return ['id' => (int) $row->id, 'name' => (string) $row->name];
        }
        if ($matches->isEmpty()) {
            return ['error' => 'No '.$label.' matched "'.$name.'".'];
        }

        $list = $matches->map(fn ($row) => $row->id.': '.$row->name)->implode(', ');

        return ['error' => 'Multiple '.$label.' matches for "'.$name.'": '.$list.'.'];
    }

    /**
     * @return array{id: int, name: string}|array{error: string}
     */
    private function resolveJournalAccount(string $type, string $name, ?int $id): array
    {
        $query = match ($type) {
            'customer' => Customer::query(),
            'supplier' => Supplier::query(),
            'investor' => Investor::query(),
            'expense' => ExpenseAccount::query(),
            'bank' => BankAccount::query(),
            'cash' => CashAccount::query(),
            'nominal' => NominalAccount::query(),
            default => null,
        };

        if (! $query) {
            return ['error' => 'Unknown journal account type.'];
        }

        return $this->resolveNamed($query, $name, $type, $id);
    }

    /**
     * @return array{id: int, name: string}|array{error: string}
     */
    private function resolveParty(string $type, string $name, ?int $id): array
    {
        if ($id) {
            $row = $this->partyQuery($type)->whereKey($id)->first(['id', 'name']);
            if (! $row) {
                return ['error' => 'No '.$type.' found with ID '.$id.'.'];
            }

            return ['id' => (int) $row->id, 'name' => (string) $row->name];
        }

        $name = trim($name);
        $query = $this->partyQuery($type);
        if ($query && $name !== '') {
            $exact = $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first(['id', 'name']);
            if ($exact) {
                return ['id' => (int) $exact->id, 'name' => (string) $exact->name];
            }
        }

        $matches = $this->searchByType($type, $name);
        if (count($matches) === 1) {
            return ['id' => $matches[0]['id'], 'name' => $matches[0]['name']];
        }
        if (count($matches) === 0) {
            return ['error' => 'No '.$type.' matched "'.$name.'". Search or create it first.'];
        }

        $list = collect($matches)->take(8)->map(fn ($m) => $m['id'].': '.$m['name'])->implode(', ');

        return ['error' => 'Multiple '.$type.' matches for "'.$name.'": '.$list.'. Tell me the exact name or ID.'];
    }

    /**
     * @return array{id: int, name: string}|array{error: string}
     */
    private function resolveBank(mixed $nameOrId): array
    {
        $banks = BankAccount::query()->orderBy('name')->get(['id', 'name', 'bank_name', 'current_balance']);
        if ($banks->isEmpty()) {
            return ['error' => 'No bank accounts exist yet. Create one first.'];
        }

        if (is_numeric($nameOrId) && (int) $nameOrId > 0) {
            $bank = $banks->firstWhere('id', (int) $nameOrId);
            if (! $bank) {
                return ['error' => 'Bank account ID not found.'];
            }

            return ['id' => (int) $bank->id, 'name' => (string) $bank->name];
        }

        $name = trim((string) $nameOrId);
        if ($name === '') {
            if ($banks->count() === 1) {
                $bank = $banks->first();

                return ['id' => (int) $bank->id, 'name' => (string) $bank->name];
            }

            $list = $banks->map(fn ($b) => $b->id.': '.$b->name)->implode(', ');

            return ['error' => 'Which bank account? '.$list];
        }

        $matches = $banks->filter(function ($bank) use ($name) {
            return Str::contains(Str::lower($bank->name.' '.$bank->bank_name), Str::lower($name));
        });

        if ($matches->count() === 1) {
            $bank = $matches->first();

            return ['id' => (int) $bank->id, 'name' => (string) $bank->name];
        }
        if ($matches->isEmpty()) {
            return ['error' => 'No bank matched "'.$name.'".'];
        }

        $list = $matches->map(fn ($b) => $b->id.': '.$b->name)->implode(', ');

        return ['error' => 'Multiple banks matched "'.$name.'": '.$list];
    }

    /**
     * @return list<array{type: string, id: int, name: string, balance: float}>
     */
    private function searchByType(string $type, string $query): array
    {
        $q = trim($query);
        if ($type === 'bank') {
            return BankAccount::query()
                ->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', '%'.$q.'%')
                        ->orWhere('bank_name', 'like', '%'.$q.'%');
                })
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'current_balance'])
                ->map(fn ($row) => [
                    'type' => 'bank',
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'balance' => (float) $row->current_balance,
                ])
                ->all();
        }

        if ($type === 'stock') {
            return StockItem::query()
                ->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', '%'.$q.'%')
                        ->orWhere('sku', 'like', '%'.$q.'%');
                })
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'cost_price'])
                ->map(fn ($row) => [
                    'type' => 'stock',
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'balance' => (float) $row->cost_price,
                ])
                ->all();
        }

        if ($type === 'stock_category') {
            return StockCategory::query()
                ->where('name', 'like', '%'.$q.'%')
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name'])
                ->map(fn ($row) => [
                    'type' => 'stock_category',
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'balance' => 0.0,
                ])
                ->all();
        }

        if ($type === 'nominal') {
            return NominalAccount::query()
                ->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', '%'.$q.'%')
                        ->orWhere('code', 'like', '%'.$q.'%');
                })
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'type'])
                ->map(fn ($row) => [
                    'type' => 'nominal',
                    'id' => (int) $row->id,
                    'name' => (string) $row->name.' ('.$row->type.')',
                    'balance' => 0.0,
                ])
                ->all();
        }

        $modelQuery = $this->partyQuery($type);
        if (! $modelQuery) {
            return [];
        }

        return $modelQuery
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', $type === 'expense' ? 'total_spent' : 'current_balance'])
            ->map(fn ($row) => [
                'type' => $type,
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'balance' => (float) ($row->current_balance ?? $row->total_spent ?? 0),
            ])
            ->all();
    }

    private function partyQuery(string $type): mixed
    {
        return match ($type) {
            'customer' => Customer::query(),
            'supplier' => Supplier::query(),
            'investor' => Investor::query(),
            'expense' => ExpenseAccount::query(),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{reply: string, pending: array<string, mixed>}
     */
    private function setPending(string $kind, array $payload, string $summary): array
    {
        if ($kind === 'customer' && trim((string) ($payload['name'] ?? '')) === '') {
            return ['reply' => 'Customer name is required.', 'pending' => session('assistant.pending')];
        }
        if ($kind === 'supplier' && trim((string) ($payload['name'] ?? '')) === '') {
            return ['reply' => 'Supplier name is required.', 'pending' => session('assistant.pending')];
        }

        $pending = [
            'id' => (string) Str::uuid(),
            'kind' => $kind,
            'payload' => $payload,
            'summary' => $summary,
        ];
        session(['assistant.pending' => $pending]);

        return [
            'reply' => $summary."\n\nClick Confirm to save, or Cancel if this is wrong.",
            'pending' => $pending,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tools(): array
    {
        $recordTypes = ['customer', 'supplier', 'investor', 'bank_account', 'expense_account', 'stock_category', 'nominal_account'];

        return [
            [
                'name' => 'search_accounts',
                'description' => 'Search customers, suppliers, investors, expenses, banks, stock items, stock categories, or nominal accounts.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'enum' => ['all', 'customer', 'supplier', 'investor', 'expense', 'bank', 'stock', 'stock_category', 'nominal']],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'get_summary',
                'description' => 'Get cash, bank, receivable, payable, and bank in/out totals.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass,
                ],
            ],
            [
                'name' => 'get_party_ledger',
                'description' => 'Get a party ledger / khata / hisab for a customer, supplier, investor, or expense. Use when the user asks for a ledger, khata, statement, or "ka ledger".',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'party_name' => ['type' => 'string'],
                        'account_type' => ['type' => 'string', 'enum' => ['all', 'customer', 'supplier', 'investor', 'expense']],
                        'account_id' => ['type' => 'integer'],
                        'from_date' => ['type' => 'string'],
                        'to_date' => ['type' => 'string'],
                    ],
                    'required' => ['party_name'],
                ],
            ],
            [
                'name' => 'draft_cash_voucher',
                'description' => 'Prepare a cash/bank receive or payment voucher. Does not save until confirmed.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['receive', 'payment']],
                        'payment_method' => ['type' => 'string', 'enum' => ['cash', 'bank']],
                        'amount' => ['type' => 'number'],
                        'account_type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'investor', 'expense', 'other']],
                        'party_name' => ['type' => 'string'],
                        'account_id' => ['type' => 'integer'],
                        'bank_name' => ['type' => 'string'],
                        'bank_account_id' => ['type' => 'integer'],
                        'voucher_date' => ['type' => 'string'],
                        'reference' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                        'other_name' => ['type' => 'string'],
                    ],
                    'required' => ['type', 'payment_method', 'amount', 'account_type'],
                ],
            ],
            [
                'name' => 'draft_record',
                'description' => 'Prepare a master record: customer, supplier, investor, bank account, expense account, stock category, or nominal account. Does not save until confirmed.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'record_type' => ['type' => 'string', 'enum' => $recordTypes],
                        'name' => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'opening_balance' => ['type' => 'number'],
                        'opening_investment' => ['type' => 'number'],
                        'bank_name' => ['type' => 'string'],
                        'account_number' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'code' => ['type' => 'string'],
                        'nominal_type' => ['type' => 'string', 'enum' => ['asset', 'liability', 'equity', 'income', 'expense']],
                    ],
                    'required' => ['record_type', 'name'],
                ],
            ],
            [
                'name' => 'draft_stock_item',
                'description' => 'Prepare a stock item. Does not save until confirmed.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'category_name' => ['type' => 'string'],
                        'sku' => ['type' => 'string'],
                        'unit' => ['type' => 'string'],
                        'cost_price' => ['type' => 'number'],
                        'sale_price' => ['type' => 'number'],
                        'reorder_level' => ['type' => 'integer'],
                    ],
                    'required' => ['name', 'category_name'],
                ],
            ],
            [
                'name' => 'draft_stock_movement',
                'description' => 'Prepare stock in, out, or adjust. Does not save until confirmed.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['in', 'out', 'adjust']],
                        'item_name' => ['type' => 'string'],
                        'stock_item_id' => ['type' => 'integer'],
                        'quantity' => ['type' => 'number'],
                        'unit_cost' => ['type' => 'number'],
                        'moved_at' => ['type' => 'string'],
                        'reference' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['type', 'quantity'],
                ],
            ],
            [
                'name' => 'draft_purchase_order',
                'description' => 'Prepare a purchase order with line items. Does not save until confirmed.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'supplier_name' => ['type' => 'string'],
                        'supplier_id' => ['type' => 'integer'],
                        'po_date' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'item_name' => ['type' => 'string'],
                                    'stock_item_id' => ['type' => 'integer'],
                                    'quantity' => ['type' => 'number'],
                                    'unit_price' => ['type' => 'number'],
                                    'unit' => ['type' => 'string'],
                                    'note' => ['type' => 'string'],
                                ],
                                'required' => ['quantity', 'unit_price'],
                            ],
                        ],
                    ],
                    'required' => ['items'],
                ],
            ],
            [
                'name' => 'draft_journal_voucher',
                'description' => 'Prepare a journal voucher with balanced debit/credit lines. Does not save until confirmed.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'voucher_date' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                        'lines' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'account_type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'investor', 'expense', 'bank', 'cash', 'nominal']],
                                    'account_name' => ['type' => 'string'],
                                    'account_id' => ['type' => 'integer'],
                                    'debit' => ['type' => 'number'],
                                    'credit' => ['type' => 'number'],
                                    'line_note' => ['type' => 'string'],
                                ],
                                'required' => ['account_type'],
                            ],
                        ],
                    ],
                    'required' => ['lines'],
                ],
            ],
        ];
    }

    private function systemPrompt(string $userMessage = ''): string
    {
        $today = now()->toDateString();
        $language = $this->languageInstruction($userMessage);

        return <<<PROMPT
You are the accounts assistant. Understand English, Urdu, Hindi, and Roman Urdu.
{$language}
Use tools for lookups and drafts. Never invent IDs. For ledger/khata/hisab, call get_party_ledger.
Search before drafting against a name. Drafts are not saved until Confirm.
If a name is ambiguous, ask which ID. Keep replies short. No user creation.
Today: {$today}.
PROMPT;
    }

    private function languageInstruction(string $message): string
    {
        return match ($this->detectReplyScript($message)) {
            'urdu' => 'THIS TURN YOU MUST reply only in Urdu script (اردو). Do not use English sentences or Roman Urdu. Keep names, amounts, and voucher numbers as-is.',
            'hindi' => 'THIS TURN YOU MUST reply only in Hindi Devanagari. Keep names and numbers as-is.',
            default => 'THIS TURN reply in English.',
        };
    }

    private function messageWithLanguageHint(string $message): string
    {
        return match ($this->detectReplyScript($message)) {
            'urdu' => $message."\n\n(جواب صرف اردو رسم الخط میں دیں۔ انگریزی یا رومن اردو میں نہ لکھیں۔)",
            'hindi' => $message."\n\n(उत्तर केवल हिन्दी देवनागरी में दें।)",
            default => $message,
        };
    }

    private function detectReplyScript(string $message): string
    {
        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $message)) {
            return 'urdu';
        }
        if (preg_match('/[\x{0900}-\x{097F}]/u', $message)) {
            return 'hindi';
        }

        $romanHits = preg_match_all(
            '/\b(mjh|mjhy|mje|mujhe|mujhy|mujh|chahiye|chahye|chaiye|dikhao|dikhae|dikha|batao|btao|hisab|hesab|khata|khate|wasool|wasul|krdo|kardo|kro|nahi|nahin|kya|kyun|kitna|kitni|kitne)\b/iu',
            $message
        );

        if ($romanHits > 0 || preg_match('/\bka\s+(ledger|khata|hisab|hesab|report|statement)\b/i', $message)) {
            return 'urdu';
        }

        return 'english';
    }

    private function userFacingAiError(string $message, Throwable $e): string
    {
        $detail = $e->getMessage();
        $rateLimited = str_contains($detail, '429') || str_contains(strtolower($detail), 'rate limit');
        $urdu = $this->detectReplyScript($message) === 'urdu';

        if ($rateLimited) {
            return $urdu
                ? "AI کی حد اس منٹ کے لیے پوری ہو گئی ہے۔ 15 سیکنڈ بعد دوبارہ کوشش کریں، یا کمانڈ لکھیں:\nreceive 50000 cash from customer Ali"
                : "The free AI limit for this minute is full. Wait about 15 seconds and try again, or use a command like:\nreceive 50000 cash from customer Ali";
        }

        $detail = preg_replace('/gsk_[A-Za-z0-9]+/', 'gsk_***', $detail) ?? $detail;
        $detail = preg_replace('/AQ\.[A-Za-z0-9_\-]+/', 'AQ.***', $detail) ?? $detail;
        $detail = preg_replace('/org_[a-z0-9]+/i', 'org_***', $detail) ?? $detail;

        return $urdu
            ? 'AI سروس دستیاب نہیں۔ کمانڈ استعمال کر سکتے ہیں: receive 50000 cash from customer Ali'
            : 'The assistant could not reach the AI service: '.$detail."\nYou can still use commands like: receive 50000 cash from customer Ali.";
    }

    /**
     * @return array{party_name: string, account_type: string}|null
     */
    private function parseLedgerCommand(string $message): ?array
    {
        if (! preg_match('/\b(ledger|khata|hisab|hesab|لیجر|کھاتہ|حساب|खाता|लेजर)\b/iu', $message)) {
            return null;
        }

        $name = '';
        $accountType = 'all';

        if (preg_match('/\b(customer|supplier|investor|expense|کستمر|سپلائر)\b/iu', $message, $typeMatch)) {
            $label = strtolower($typeMatch[1]);
            $accountType = match ($label) {
                'supplier', 'سپلائر' => 'supplier',
                'investor' => 'investor',
                'expense' => 'expense',
                default => 'customer',
            };
        }

        if (preg_match('/(?:mjh|mujhe|mujhy|mjhy|mje|please|show|get|want)?\s*(.+?)\s+ka\s+(?:ledger|khata|hisab|hesab)\b/iu', $message, $m)) {
            $name = trim($m[1]);
        } elseif (preg_match('/(?:ledger|khata|hisab|hesab|لیجر|کھاتہ)\s+(?:of|for|کا|کی)?\s*(.+)$/iu', $message, $m)) {
            $name = trim($m[1]);
        } elseif (preg_match('/(.+?)\s+(?:کا|کی)\s+(?:لیجر|کھاتہ|حساب)/u', $message, $m)) {
            $name = trim($m[1]);
        }

        $name = preg_replace('/\b(mjh|mujhe|mujhy|mjhy|mje|please|show|get|want|mujhe|the|a|an|customer|supplier|investor|expense)\b/iu', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name, " \t\"'");

        if ($name === '') {
            return null;
        }

        return [
            'party_name' => $name,
            'account_type' => $accountType,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function lookupPartyLedger(array $input): array
    {
        $name = trim((string) ($input['party_name'] ?? $input['query'] ?? ''));
        $type = strtolower(trim((string) ($input['account_type'] ?? 'all')));
        $accountId = isset($input['account_id']) ? (int) $input['account_id'] : 0;

        if ($name === '' && $accountId < 1) {
            return ['error' => 'Party name is required.'];
        }

        $types = in_array($type, ['customer', 'supplier', 'investor', 'expense'], true)
            ? [$type]
            : ['customer', 'supplier', 'investor', 'expense'];

        $matches = [];
        if ($accountId > 0 && count($types) === 1) {
            $matches[] = ['type' => $types[0], 'id' => $accountId, 'name' => $name !== '' ? $name : ('#'.$accountId)];
        } else {
            foreach ($types as $accountType) {
                foreach ($this->searchByType($accountType, $name) as $row) {
                    $matches[] = $row;
                }
            }

            $exact = array_values(array_filter($matches, fn ($row) => strcasecmp((string) $row['name'], $name) === 0));
            if (count($exact) === 1) {
                $matches = $exact;
            }
        }

        if ($matches === []) {
            return ['error' => 'No account matched "'.$name.'".'];
        }
        if (count($matches) > 1) {
            return [
                'error' => 'Multiple matches for "'.$name.'".',
                'matches' => array_map(fn ($row) => [
                    'type' => $row['type'],
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'balance' => $row['balance'] ?? null,
                ], array_slice($matches, 0, 8)),
            ];
        }

        $party = $matches[0];
        $from = Carbon::parse((string) ($input['from_date'] ?? now()->startOfYear()->toDateString()))->startOfDay();
        $to = Carbon::parse((string) ($input['to_date'] ?? now()->toDateString()))->endOfDay();

        try {
            $ledger = $this->partyLedger->ledger((string) $party['type'], (int) $party['id'], $from, $to);
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }

        $lines = [];
        foreach ($ledger['entries'] as $entry) {
            $date = $entry['date'] ?? null;
            $lines[] = [
                'date' => $date instanceof Carbon ? $date->toDateString() : (string) $date,
                'ref' => (string) ($entry['ref'] ?? ''),
                'description' => (string) ($entry['description'] ?? ''),
                'debit' => (float) ($entry['debit'] ?? 0),
                'credit' => (float) ($entry['credit'] ?? 0),
                'balance' => (float) ($entry['balance'] ?? 0),
            ];
        }

        if (count($lines) > 20) {
            $lines = array_slice($lines, -20);
        }

        return [
            'party_name' => (string) ($this->partyLedger->partyName((string) $party['type'], (int) $party['id']) ?: $party['name']),
            'account_type' => $party['type'],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'closing_balance' => (float) $ledger['closingBalance'],
            'total_debit' => (float) $ledger['totalDebit'],
            'total_credit' => (float) $ledger['totalCredit'],
            'entries' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolGetPartyLedger(array $input): string
    {
        return json_encode($this->lookupPartyLedger($input), JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatLedgerForUser(array $data, string $script): string
    {
        $urdu = $script === 'urdu';

        if (isset($data['matches']) && is_array($data['matches'])) {
            $list = collect($data['matches'])->map(fn ($row) => ($row['id'] ?? '').': '.($row['name'] ?? '').' ('.($row['type'] ?? '').')')->implode("\n");

            return $urdu
                ? "ایک سے زیادہ کھاتے ملے ہیں۔ بتائیں کون سا چاہیے:\n".$list
                : "Multiple accounts matched. Tell me which ID:\n".$list;
        }

        if (isset($data['error'])) {
            $err = (string) $data['error'];
            if (! $urdu) {
                return $err;
            }
            if (str_contains($err, 'No account matched')) {
                return 'اس نام سے کوئی کھاتہ نہیں ملا: '.trim($err, '.').'۔';
            }

            return $err;
        }

        $name = (string) ($data['party_name'] ?? '');
        $type = (string) ($data['account_type'] ?? '');
        $from = (string) ($data['from'] ?? '');
        $to = (string) ($data['to'] ?? '');
        $closing = (float) ($data['closing_balance'] ?? 0);
        $entries = is_array($data['entries'] ?? null) ? $data['entries'] : [];

        $typeLabel = $urdu ? match ($type) {
            'customer' => 'کسٹمر',
            'supplier' => 'سپلائر',
            'investor' => 'سرمایہ کار',
            'expense' => 'خرچہ',
            default => $type,
        } : $type;

        $meaning = '';
        if (in_array($type, ['customer', 'expense'], true)) {
            $meaning = $closing > 0.005
                ? ($urdu ? ' (وصولی باقی)' : ' (receivable)')
                : ($closing < -0.005 ? ($urdu ? ' (جمع باقی)' : ' (advance/credit)') : '');
        } elseif (in_array($type, ['supplier', 'investor'], true)) {
            $meaning = $closing > 0.005
                ? ($urdu ? ' (ادا کرنا باقی)' : ' (payable)')
                : ($closing < -0.005 ? ($urdu ? ' (پیشگی/جمع)' : ' (advance)') : '');
        }

        $lines = [];
        $lines[] = $urdu
            ? $name.' کا لیجر ('.$typeLabel.') '.$from.' سے '.$to
            : $name.' ledger ('.$typeLabel.') '.$from.' to '.$to;
        $lines[] = $urdu
            ? 'اختتامی بیلنس: '.number_format($closing, 2).$meaning
            : 'Closing balance: '.number_format($closing, 2).$meaning;
        $lines[] = $urdu
            ? 'کل ڈیبٹ: '.number_format((float) ($data['total_debit'] ?? 0), 2).' | کل کریڈٹ: '.number_format((float) ($data['total_credit'] ?? 0), 2)
            : 'Total debit: '.number_format((float) ($data['total_debit'] ?? 0), 2).' | Total credit: '.number_format((float) ($data['total_credit'] ?? 0), 2);

        if ($entries === []) {
            $lines[] = $urdu ? 'اس مدت میں کوئی اندراج نہیں۔' : 'No entries in this period.';

            return implode("\n", $lines);
        }

        $lines[] = $urdu ? "تاریخ | ریف | تفصیل | ڈیبٹ | کریڈٹ | بیلنس" : 'Date | Ref | Detail | Debit | Credit | Balance';
        foreach ($entries as $entry) {
            $lines[] = ($entry['date'] ?? '').' | '.($entry['ref'] ?? '').' | '.($entry['description'] ?? '').' | '
                .number_format((float) ($entry['debit'] ?? 0), 2).' | '
                .number_format((float) ($entry['credit'] ?? 0), 2).' | '
                .number_format((float) ($entry['balance'] ?? 0), 2);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  mixed  $content
     */
    private function textFromContent(mixed $content): string
    {
        if (is_string($content)) {
            return trim($content);
        }
        if (! is_array($content)) {
            return 'Done.';
        }

        $parts = [];
        foreach ($content as $block) {
            if (($block['type'] ?? '') === 'text') {
                $parts[] = $block['text'] ?? '';
            }
        }

        $text = trim(implode("\n", $parts));

        return $text !== '' ? $text : 'Draft is ready. Confirm to save.';
    }

    /**
     * @param  array<string, mixed>  $choice
     * @return array<string, mixed>
     */
    private function assistantMessageForHistory(array $choice): array
    {
        $message = [
            'role' => 'assistant',
            'content' => $choice['content'] ?? null,
        ];

        if (! empty($choice['tool_calls']) && is_array($choice['tool_calls'])) {
            $message['tool_calls'] = $choice['tool_calls'];
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $choice
     */
    private function finalAssistantReply(array $choice): string
    {
        $reply = trim((string) ($choice['content'] ?? ''));
        $pending = session('assistant.pending');

        if ($reply !== '') {
            return $reply;
        }

        if (is_array($pending) && filled($pending['summary'] ?? null)) {
            return (string) $pending['summary'].' Confirm to save.';
        }

        return 'Draft is ready. Confirm to save.';
    }

    private function decodeToolArguments(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array{reply: string, pending: ?array<string, mixed>}  $result
     */
    private function encodeDraftResult(array $result): string
    {
        $pending = $result['pending'] ?? null;

        return json_encode([
            'message' => $result['reply'] ?? '',
            'queued' => is_array($pending),
            'saved' => false,
            'summary' => is_array($pending) ? ($pending['summary'] ?? null) : null,
        ], JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    private function isConfirm(string $message): bool
    {
        return (bool) preg_match('/^(yes|y|ok|okay|confirm|save|haan|han|ji|theek hai|go ahead)\.?$/i', $message);
    }

    private function isCancel(string $message): bool
    {
        return (bool) preg_match('/^(no|n|cancel|stop|nah|nahi|dont|do not)\.?$/i', $message);
    }
}
