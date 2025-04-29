<?php

declare(strict_types=1);

namespace AbraFlexi\Cash;

/**
 * Cashier - Vysavač pokladny.
 */
class Cashier extends \AbraFlexi\PokladniPohyb {

    use datescope;

    /**
     * Dry-run mód - pouze simulace bez zápisu
     * @var bool
     */
    public bool $dryRun = false;

    public function __construct(string $scope = 'last_month', $options = []) {
        parent::__construct(null, $options);
        $this->setScope($scope);
    }

    /**
     * Povolit režim suchého běhu.
     */
    public function enableDryRun(): void {
        $this->dryRun = true;
    }

    /**
     * Provést "vysávání" pokladny
     *
     * @return array Výsledky operace
     */
    public function fixAll(): array {
        $start = $this->dateScope->getStartDate();
        $end = $this->dateScope->getEndDate();
        $balance = 0.0;
        
        
        $created = 0;
        $skipped = 0;

        while ($start <= $end) {
            $periodStart = clone $start;
            $periodEnd = (clone $start)->modify('+1 week');

            $currentWeek = new \DatePeriod($periodStart, new \DateInterval('P1D'), $periodEnd);

            $this->addStatusMessage("Processing " . $periodStart->format('Y-m-d'));

            $weekIncome = $this->getCashIncome($currentWeek);
            
            $balance += $weekIncome;

            if ($weekIncome > 0.0) {
                if ($this->dryRun) {
                    echo "   -> DRY RUN: Would create expense for {$balance} Kč\n";
                } else {
                    $expense = $this->createCashExpense($weekIncome, $periodEnd);
                    if ($expense->getRecordIdent()) {
                        $this->addStatusMessage($expense->getRecordId(). ' '.  $expense->getRecordIdent() . "   -> Expense created.",'success');
                        $created++;
                    } else {
                        $skipped++;
                    }
                }
            } else {
                $skipped++;
            }

            $start->modify('+1 week');
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * Získat zůstatek pokladny k danému datu
     *
     * @param \DateTimeInterface $date
     *
     * @return float
     */
    protected function getCashIncome(\DatePeriod $datePeriod): float {
        // TODO: Zde získat reálný zůstatek pokladny přes AbraFlexi API
        $weekSumData = $this->getSumsFromAbraFlexi(['sumCelkem'],['datVyst' => $datePeriod,'typPohybuK' => 'typPohybu.prijem']);

        // MOCK: Vracíme náhodné číslo jen pro simulaci
        return array_key_exists('group', $weekSumData) ? (float) $weekSumData['group']['value']['value']['income'] : 0.0;
    }

    /**
     * Vytvořit výdajový pokladní doklad
     *
     * @param float $amount
     * @param \DateTimeInterface $date
     *
     * @return bool
     */
    protected function createCashExpense(float $amount, \DateTimeInterface $date): self {
        $this->dataReset();
        $day = $date->modify('+1 day');
        $this->setData([
            'typPohybuK' => 'typPohybu.vydej', // Výdej
            'datVyst' => new \AbraFlexi\Date($day->format('Y-m-d')),
            'datUcto' => new \AbraFlexi\Date($day->format('Y-m-d')),
            'popis' => 'Osobní spotřeba',
            'sumZklCelkem' => $amount,
            'sumCelkem' => $amount,
            'duzpPuv' => $date->modify('+1 week'),
            'rada' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_ROW')),
            'typDokl' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_TYPE')),
            'pokladna' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_BOX')),
            'stavUzivK' => 'stavUziv.genKasa'
        ]);

        $subitem = [
            'typPolozkyK' => 'typPolozky.ucetni',
            'stavUzivK' => 'stavUziv.genKasa',
            'autogen' => true,
            'datVyst' => $day,
            'cenaMj' => $amount,
            'sumZkl' => $amount,
            'sumCelkem'=> $amount,
            'mnozMj' => 1,
            'kopTypUcOp' => true,
            'nazev' => 'výběr z pokladny'
        ];

        $this->addArrayToBranch($subitem);

        if (\Ease\Shared::cfg('ABRAFLEXI_CASH_ACCOUNTING')) {
            $this->setDataValue('typUcOp', \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_ACCOUNTING')));
        }

        $this->sync();
        return $this;
    }
}
