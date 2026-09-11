<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ExpenseAccount;
use App\Models\InboxItem;
use App\Models\Supplier;
use App\Services\PurchaseBillService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class InboxController extends Controller
{
    public function index(): View
    {
        $items = InboxItem::query()->orderByDesc('id')->limit(200)->get();

        return view('inbox.index', compact('items'));
    }

    public function create(): View
    {
        return view('inbox.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:receipt,bill,bank_statement'],
            'title' => ['nullable', 'string', 'max:255'],
            'extracted_amount' => ['nullable', 'numeric', 'min:0'],
            'extracted_date' => ['nullable', 'date'],
            'extracted_party' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $file = $request->file('file');
        $path = $file->store('inbox', 'public');

        InboxItem::create([
            'type' => $data['type'],
            'status' => 'new',
            'title' => $data['title'] ?: $file->getClientOriginalName(),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extracted_amount' => $data['extracted_amount'] ?? null,
            'extracted_date' => $data['extracted_date'] ?? now()->toDateString(),
            'extracted_party' => $data['extracted_party'] ?? null,
            'notes' => $data['notes'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('inbox.index')->with('success', 'Document added to Inbox.');
    }

    public function show(InboxItem $inbox): View
    {
        $expenseAccounts = ExpenseAccount::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('inbox.show', compact('inbox', 'expenseAccounts', 'bankAccounts', 'suppliers'));
    }

    public function postExpense(Request $request, InboxItem $inbox): RedirectResponse
    {
        if (! $inbox->isNew()) {
            return back()->with('error', 'This item is already processed.');
        }

        $data = $request->validate([
            'expense_account_id' => ['required', 'exists:expense_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        $voucher = app(\App\Services\CashVoucherService::class)->create([
            'type' => 'payment',
            'payment_method' => $data['payment_method'],
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'account_type' => 'expense',
            'account_id' => $data['expense_account_id'],
            'amount' => $data['amount'],
            'voucher_date' => ($inbox->extracted_date ?? now())->toDateString(),
            'notes' => 'From inbox: '.$inbox->title,
        ]);

        $inbox->update([
            'status' => 'posted',
            'posted_type' => 'cash_voucher',
            'posted_id' => $voucher->id,
        ]);

        return redirect()->route('inbox.index')->with('success', 'Expense posted from Inbox.');
    }

    public function postBill(Request $request, InboxItem $inbox, PurchaseBillService $bills, SettingsService $settings): RedirectResponse
    {
        if (! $inbox->isNew()) {
            return back()->with('error', 'This item is already processed.');
        }

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $amount = (float) $data['amount'];
        $rate = $settings->vatRate();
        $net = round($amount / (1 + $rate / 100), 2);

        try {
            $bill = $bills->saveDraft(null, [
                'supplier_id' => $data['supplier_id'],
                'bill_date' => ($inbox->extracted_date ?? now())->toDateString(),
                'notes' => 'From inbox: '.$inbox->title,
            ], [[
                'description' => $inbox->title ?: 'Inbox bill',
                'quantity' => 1,
                'unit_price' => $net,
                'vat_rate' => $rate,
            ]], $request->user());
            $bill = $bills->post($bill);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $inbox->update([
            'status' => 'posted',
            'posted_type' => 'purchase_bill',
            'posted_id' => $bill->id,
        ]);

        return redirect()->route('bills.show', $bill)->with('success', 'Bill created from Inbox.');
    }

    public function reject(InboxItem $inbox): RedirectResponse
    {
        $inbox->update(['status' => 'rejected']);

        return redirect()->route('inbox.index')->with('success', 'Inbox item rejected.');
    }

    public function destroy(InboxItem $inbox): RedirectResponse
    {
        $inbox->delete();

        return redirect()->route('inbox.index')->with('success', 'Inbox item deleted.');
    }
}
