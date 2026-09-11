<?php

namespace App\Http\Controllers;

use App\Models\InboxItem;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Services\SettingsService;
use Illuminate\View\View;

class GetStartedController extends Controller
{
    public function __invoke(SettingsService $settings): View
    {
        $user = auth()->user();
        $firstName = explode(' ', (string) $user?->name)[0] ?? 'there';

        return view('get-started.index', [
            'firstName' => $firstName,
            'hasInvoice' => SalesInvoice::query()->exists(),
            'hasInbox' => InboxItem::query()->exists(),
            'hasBill' => PurchaseBill::query()->exists(),
            'companyVat' => (string) $settings->get('company_vat_number', ''),
            'plan' => $settings->plan(),
        ]);
    }
}
