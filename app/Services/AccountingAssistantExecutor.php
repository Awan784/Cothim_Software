<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Investor;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\NominalAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AccountingAssistantExecutor
{
    public function __construct(
        private CashVoucherService $vouchers,
        private InventoryService $inventory,
    ) {}

    /**
     * @param  array<string, mixed>  $pending
     * @return array{reply: string, pending: ?array<string, mixed>}
     */
    public function execute(User $user, array $pending): array
    {
        $kind = (string) ($pending['kind'] ?? '');
        $payload = is_array($pending['payload'] ?? null) ? $pending['payload'] : [];

        $denied = $this->permissionError($user, $kind);
        if ($denied) {
            return ['reply' => $denied, 'pending' => $pending];
        }

        $reply = match ($kind) {
            'cash_voucher' => $this->saveCashVoucher($payload),
            'customer' => $this->saveCustomer($payload),
            'supplier' => $this->saveSupplier($payload),
            'investor' => $this->saveInvestor($payload),
            'bank_account' => $this->saveBankAccount($payload),
            'expense_account' => $this->saveExpenseAccount($payload),
            'nominal_account' => $this->saveNominalAccount($payload),
            'stock_category' => $this->saveStockCategory($payload),
            'stock_item' => $this->saveStockItem($payload),
            'stock_movement' => $this->saveStockMovement($payload),
            'purchase_order' => $this->savePurchaseOrder($payload),
            'journal_voucher' => $this->saveJournalVoucher($payload),
            default => null,
        };

        if ($reply === null) {
            session()->forget('assistant.pending');

            return ['reply' => 'Unknown draft. Nothing was saved.', 'pending' => null];
        }

        session()->forget('assistant.pending');

        return ['reply' => $reply, 'pending' => null];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveCashVoucher(array $payload): string
    {
        $voucher = $this->vouchers->create($payload);
        CashVoucher::loadAccountNames(collect([$voucher]));
        $party = $voucher->account_type === 'other'
            ? ($voucher->other_name ?? 'Other')
            : ($voucher->account_display_name ?? 'account');

        return 'Saved voucher '.$voucher->voucher_no.': '.ucfirst((string) $voucher->type).' '.number_format((float) $voucher->amount, 2).' ('.($voucher->payment_method ?? 'cash').') for '.$party.'.';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveCustomer(array $payload): string
    {
        $opening = (float) ($payload['opening_balance'] ?? 0);
        $customer = Customer::create([
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
        ]);

        return 'Saved customer '.$customer->name.' (ID '.$customer->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveSupplier(array $payload): string
    {
        $opening = (float) ($payload['opening_balance'] ?? 0);
        $supplier = Supplier::create([
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
        ]);

        return 'Saved supplier '.$supplier->name.' (ID '.$supplier->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveInvestor(array $payload): string
    {
        $opening = (float) ($payload['opening_investment'] ?? $payload['opening_balance'] ?? 0);
        $investor = Investor::create([
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
            'opening_investment' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
        ]);

        return 'Saved investor '.$investor->name.' (ID '.$investor->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveBankAccount(array $payload): string
    {
        $opening = (float) ($payload['opening_balance'] ?? 0);
        $bank = BankAccount::create([
            'name' => $payload['name'],
            'bank_name' => $payload['bank_name'] ?? null,
            'account_number' => $payload['account_number'] ?? null,
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
        ]);

        return 'Saved bank account '.$bank->name.' (ID '.$bank->id.') with balance '.number_format($opening, 2).'.';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveExpenseAccount(array $payload): string
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Expense account name is required.');
        }
        if (ExpenseAccount::where('name', $name)->exists()) {
            throw new InvalidArgumentException('Expense account "'.$name.'" already exists.');
        }

        $account = ExpenseAccount::create([
            'name' => $name,
            'description' => $payload['description'] ?? null,
            'is_active' => true,
            'total_spent' => 0,
        ]);

        return 'Saved expense account '.$account->name.' (ID '.$account->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveNominalAccount(array $payload): string
    {
        $type = strtolower((string) ($payload['type'] ?? 'asset'));
        if (! in_array($type, ['asset', 'liability', 'equity', 'income', 'expense'], true)) {
            throw new InvalidArgumentException('Nominal type must be asset, liability, equity, income, or expense.');
        }

        $account = NominalAccount::create([
            'parent_id' => $payload['parent_id'] ?? null,
            'code' => $payload['code'] ?? null,
            'name' => $payload['name'],
            'type' => $type,
            'is_active' => true,
        ]);

        return 'Saved nominal account '.$account->name.' ('.$type.', ID '.$account->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveStockCategory(array $payload): string
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Stock category name is required.');
        }
        if (StockCategory::where('name', $name)->exists()) {
            throw new InvalidArgumentException('Stock category "'.$name.'" already exists.');
        }

        $category = StockCategory::create([
            'name' => $name,
            'description' => $payload['description'] ?? null,
            'is_active' => true,
        ]);

        return 'Saved stock category '.$category->name.' (ID '.$category->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveStockItem(array $payload): string
    {
        $item = StockItem::create([
            'stock_category_id' => (int) $payload['stock_category_id'],
            'sku' => $payload['sku'] ?? null,
            'name' => $payload['name'],
            'unit' => $payload['unit'] ?? null,
            'cost_price' => (float) ($payload['cost_price'] ?? 0),
            'sale_price' => (float) ($payload['sale_price'] ?? 0),
            'reorder_level' => (int) ($payload['reorder_level'] ?? 0),
            'is_active' => true,
        ]);

        return 'Saved stock item '.$item->name.' (ID '.$item->id.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveStockMovement(array $payload): string
    {
        $movement = StockMovement::create([
            'stock_item_id' => (int) $payload['stock_item_id'],
            'type' => $payload['type'],
            'quantity' => (float) $payload['quantity'],
            'unit_cost' => $payload['unit_cost'] ?? null,
            'moved_at' => $payload['moved_at'] ?? now(),
            'reference' => $payload['reference'] ?? null,
            'notes' => $payload['notes'] ?? 'Created via assistant',
        ]);

        return 'Saved stock '.$movement->type.' of '.number_format((float) $movement->quantity, 2).' for item ID '.$movement->stock_item_id.'.';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function savePurchaseOrder(array $payload): string
    {
        $items = $payload['items'] ?? [];
        if (! is_array($items) || $items === []) {
            throw new InvalidArgumentException('Purchase order needs at least one item.');
        }

        $po = DB::transaction(function () use ($payload, $items) {
            $supplier = Supplier::whereKey($payload['supplier_id'])->lockForUpdate()->firstOrFail();
            $total = 0.0;
            foreach ($items as $row) {
                $total += ((float) $row['unit_price']) * ((float) $row['quantity']);
            }

            $order = PurchaseOrder::create([
                'po_no' => PurchaseOrder::nextNumber(),
                'supplier_id' => $supplier->id,
                'po_date' => $payload['po_date'] ?? now()->toDateString(),
                'notes' => $payload['notes'] ?? 'Created via assistant',
                'total_amount' => $total,
            ]);

            foreach ($items as $row) {
                $unitPrice = (float) $row['unit_price'];
                $qty = (float) $row['quantity'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'stock_item_id' => $row['stock_item_id'] ?? null,
                    'item_name' => $row['item_name'],
                    'unit' => $row['unit'] ?? null,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => $unitPrice * $qty,
                    'note' => $row['note'] ?? null,
                ]);

                if (! empty($row['stock_item_id'])) {
                    $this->inventory->receive((int) $row['stock_item_id'], $qty, [
                        'unit_cost' => $unitPrice,
                        'moved_at' => $order->po_date,
                        'reference' => $order->po_no,
                        'notes' => $row['item_name'] ?? 'Purchase order',
                        'source_type' => 'purchase_order',
                        'source_id' => $order->id,
                    ]);
                }
            }

            $supplier->increment('current_balance', $total);

            return $order;
        });

        return 'Saved purchase order '.$po->po_no.' total '.number_format((float) $po->total_amount, 2).'.';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveJournalVoucher(array $payload): string
    {
        $lines = $payload['lines'] ?? [];
        if (! is_array($lines) || count($lines) < 2) {
            throw new InvalidArgumentException('Journal voucher needs at least two lines.');
        }

        $debit = 0.0;
        $credit = 0.0;
        foreach ($lines as $line) {
            $debit += (float) ($line['debit'] ?? 0);
            $credit += (float) ($line['credit'] ?? 0);
        }
        if (abs($debit - $credit) > 0.009) {
            throw new InvalidArgumentException('Journal debit ('.number_format($debit, 2).') must equal credit ('.number_format($credit, 2).').');
        }

        $voucher = DB::transaction(function () use ($payload, $lines, $debit, $credit) {
            $voucher = JournalVoucher::create([
                'voucher_no' => 'JV-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'voucher_date' => $payload['voucher_date'] ?? now()->toDateString(),
                'notes' => $payload['notes'] ?? 'Created via assistant',
                'total_debit' => $debit,
                'total_credit' => $credit,
            ]);

            foreach ($lines as $line) {
                $lineModel = JournalVoucherLine::create([
                    'journal_voucher_id' => $voucher->id,
                    'account_type' => $line['account_type'],
                    'account_id' => $line['account_id'],
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                    'line_note' => $line['line_note'] ?? null,
                ]);
                $this->applyJournalLine($lineModel, true);
            }

            return $voucher;
        });

        return 'Saved journal voucher '.$voucher->voucher_no.' ('.number_format($debit, 2).' Dr / '.number_format($credit, 2).' Cr).';
    }

    private function applyJournalLine(JournalVoucherLine $line, bool $apply): void
    {
        if ($line->account_type === 'nominal') {
            return;
        }

        $debit = (float) $line->debit;
        $credit = (float) $line->credit;
        $multiplier = $apply ? 1 : -1;

        match ($line->account_type) {
            'customer' => Customer::whereKey($line->account_id)->lockForUpdate()->firstOrFail()->increment('current_balance', ($debit - $credit) * $multiplier),
            'supplier' => Supplier::whereKey($line->account_id)->lockForUpdate()->firstOrFail()->increment('current_balance', ($credit - $debit) * $multiplier),
            'investor' => Investor::whereKey($line->account_id)->lockForUpdate()->firstOrFail()->increment('current_balance', ($credit - $debit) * $multiplier),
            'expense' => ExpenseAccount::syncTotalSpentFor((int) $line->account_id),
            'bank' => BankAccount::whereKey($line->account_id)->lockForUpdate()->firstOrFail()->increment('current_balance', ($debit - $credit) * $multiplier),
            'cash' => CashAccount::whereKey($line->account_id)->lockForUpdate()->firstOrFail()->increment('current_balance', ($debit - $credit) * $multiplier),
            default => null,
        };
    }

    private function permissionError(User $user, string $kind): ?string
    {
        $map = [
            'cash_voucher' => ['cash-vouchers', 'cash vouchers'],
            'customer' => ['customers', 'customers'],
            'supplier' => ['suppliers', 'suppliers'],
            'investor' => ['investors', 'investors'],
            'bank_account' => ['bank-accounts', 'bank accounts'],
            'expense_account' => ['expense-accounts', 'expense accounts'],
            'nominal_account' => ['nominal-accounts', 'nominal accounts'],
            'stock_category' => ['stock-categories', 'stock categories'],
            'stock_item' => ['stock-items', 'stock items'],
            'stock_movement' => ['stock-movements', 'stock movements'],
            'purchase_order' => ['purchase-orders', 'purchase orders'],
            'journal_voucher' => ['journal-vouchers', 'journal vouchers'],
        ];

        if (! isset($map[$kind])) {
            return null;
        }

        [$module, $label] = $map[$kind];
        if (! $user->canModule($module, 'create')) {
            return 'You do not have permission to create '.$label.'.';
        }

        return null;
    }
}
