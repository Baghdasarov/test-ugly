<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommissionService
{
    /**
     * @return array
     */
    public function getTransactions(): array
    {
        $transactions = [];
        $fileContent = file(database_path('input.txt'), FILE_IGNORE_NEW_LINES);

        foreach ($fileContent as $data) {
            if ($transaction = json_decode($data)) {
                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * @return array
     */
    public function calculateCommissions(): array
    {
        $transactions = $this->getTransactions();
        $commissions = [];

        foreach ($transactions as $transaction) {
            if ($transaction->bin) {
                $binResult = $this->getBinData($transaction->bin);
                $isEu = in_array($binResult['country']['alpha2'], config('eu-countries'));
                $exchange = $this->getExchangeData($transaction->currency);

                if (isset($exchange['rates'][$transaction->currency])) {
                    $commissions[] = $this->getCommission((float)$transaction->amount, $exchange['rates'][$transaction->currency], $isEu);
                }
            }
        }

        return $commissions;
    }

    /**
     * @param float $amount
     * @param float $rate
     * @param bool $eu
     * @return float
     */
    public function getCommission(float $amount, float $rate, bool $eu = false): float
    {
        $amount = ($eu || $rate == 0) ? $amount : $amount / $rate;

        return round($amount * config('commission.commissions.' . ($eu ? 'eu' : 'others')) * 2) / 2;
    }

    /**
     * @param int $binNumber
     * @return Collection|null
     */
    public function getBinData(int $binNumber): ?Collection
    {
        try {
            return Http::get(Str::finish(config('commission.bin_list_api_url'), '/') . $binNumber)->throw()->collect();
        } catch (\Exception $e) {
            Log::critical($e->getMessage());
            return null;
        }
    }

    /**
     * @param string $currency
     * @return Collection|null
     */
    public function getExchangeData(string $currency): ?Collection
    {
        try {
            return Http::withHeaders([
                    'apikey' => config('commission.exchanger_api_key')
                ])->get(Str::finish(config('commission.exchanger_api_url'), '/') . 'exchangerates_data/latest')
                ->throw()
                ->collect();

        } catch (\Exception $e) {
            Log::critical($e->getMessage());
            return null;
        }
    }
}
