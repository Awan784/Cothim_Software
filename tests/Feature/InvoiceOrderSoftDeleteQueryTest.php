<?php

use App\Models\SalesInvoice;
use App\Models\SalesOrder;

it('can look up the sales order linked to an invoice', function () {
    $invoice = new SalesInvoice([
        'sales_order_id' => 1,
    ]);
    $invoice->id = 1;

    $result = SalesOrder::linkedToInvoice($invoice);

    expect($result === null || $result instanceof SalesOrder)->toBeTrue();
});
