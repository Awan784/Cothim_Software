<?php

use App\Models\StockItemLot;

it('trims batch numbers used to match lots', function () {
    expect(StockItemLot::normalizeBatch(' 002 '))->toBe('002');
    expect(StockItemLot::normalizeBatch(null))->toBe('');
    expect(StockItemLot::normalizeBatch(''))->toBe('');
});
