<?php

namespace App\Services;

use App\Models\Salesman;

class CommissionService
{
    /**
     * @return array{company_retain_percent: float, salesman_commission_percent: float}
     */
    public function ratesFor(?Salesman $salesman): array
    {
        $org = current_organization();
        $retain = (float) ($org?->company_retain_percent ?? 50);
        $commission = $salesman !== null && $salesman->commission_percent !== null
            ? (float) $salesman->commission_percent
            : (float) ($org?->salesman_commission_percent ?? 25);

        return [
            'company_retain_percent' => max(0, min(100, $retain)),
            'salesman_commission_percent' => max(0, min(100, $commission)),
        ];
    }

    /**
     * Company keeps retain% of the invoice total. Commission is commission% of the remaining amount.
     * Example: 1000 at 50% retain and 25% commission → retain 500, remaining 500, commission 125.
     *
     * @return array{
     *     company_retain_percent: float,
     *     salesman_commission_percent: float,
     *     company_retain_amount: float,
     *     salesman_commission_amount: float
     * }
     */
    public function split(float $total, float $retainPercent, float $commissionPercent): array
    {
        $retainPercent = max(0, min(100, $retainPercent));
        $commissionPercent = max(0, min(100, $commissionPercent));
        $companyRetain = round($total * ($retainPercent / 100), 2);
        $remaining = round($total - $companyRetain, 2);
        $salesmanCommission = round($remaining * ($commissionPercent / 100), 2);

        return [
            'company_retain_percent' => $retainPercent,
            'salesman_commission_percent' => $commissionPercent,
            'company_retain_amount' => $companyRetain,
            'salesman_commission_amount' => $salesmanCommission,
        ];
    }

    /**
     * @return array{
     *     company_retain_percent: float,
     *     salesman_commission_percent: float,
     *     company_retain_amount: float,
     *     salesman_commission_amount: float
     * }
     */
    public function snapshot(float $total, ?Salesman $salesman): array
    {
        $rates = $this->ratesFor($salesman);

        return $this->split(
            $total,
            $rates['company_retain_percent'],
            $rates['salesman_commission_percent']
        );
    }
}
