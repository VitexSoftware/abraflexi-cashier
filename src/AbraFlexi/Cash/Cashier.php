<?php

declare(strict_types=1);

/**
 * This file is part of the CashTools for AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-CashTools
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AbraFlexi\Cash;

/**
 * Cashier - Vysavač pokladny.
 */
class Cashier extends \AbraFlexi\PokladniPohyb
{
    use datescope;

    /**
     * Dry-run mód - pouze simulace bez zápisu.
     */
    public bool $dryRun = false;
    private string $expenseText;

    public function __construct(string $scope = 'last_month', $options = [])
    {
        parent::__construct(null, $options);
        $this->setScope($scope);
        $this->expenseText = \Ease\Shared::cfg('ABRAFLEXI_CASH_TEXT');
    }

    /**
     * Povolit režim suchého běhu.
     */
    public function enableDryRun(): void
    {
        $this->dryRun = true;
    }

    /**
     * Provést "vysávání" pokladny.
     *
     * @return array Výsledky operace
     */
    public function fixAll(): array
    {
        $start = $this->dateScope->getStartDate();
        $end = $this->dateScope->getEndDate();
        $balance = 0.0;

        $created = 0;
        $skipped = 0;

        $this->addStatusMessage('Fixing between '.$start->format('Y-m-d').' and '.$end->format('Y-m-d'), 'debug');

        while ($start <= $end) {
            $periodStart = clone $start;
            $periodEnd = (clone $start)->modify('+6 day');

            $currentWeek = new \DatePeriod($periodStart, new \DateInterval('P1D'), $periodEnd);

            $this->addStatusMessage('Processing '.$periodStart->format('Y-m-d').' '.$periodEnd->format('Y-m-d'));

            $weekIncome = $this->getCashIncome($currentWeek);

            $balance += $weekIncome;

            if ($weekIncome > 0.0) {
                if ($this->expenseExistsForWeek($periodEnd)) {
                    $this->addStatusMessage('   -> Expense already exists for this week. Skipping.', 'notice');
                    ++$skipped;
                } elseif ($this->dryRun) {
                    echo "   -> DRY RUN: Would create expense for {$balance} Kč\n";
                } else {
                    $expense = $this->createCashExpense($weekIncome, $periodEnd);

                    if ($expense->getRecordIdent()) {
                        $this->addStatusMessage('#'.$expense->getRecordId().' '.$expense->getRecordIdent().' '.$expense->getDataValue('datVyst').' -'.$weekIncome, 'success');
                        ++$created;
                    } else {
                        ++$skipped;
                    }
                }
            } else {
                ++$skipped;
            }

            $start->modify('+1 week');
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * Získat zůstatek pokladny k danému datu.
     */
    protected function getCashIncome(\DatePeriod $datePeriod): float
    {
        $weekSumData = $this->getSumsFromAbraFlexi(['sumCelkem'], ['datVyst' => $datePeriod, 'typPohybuK' => 'typPohybu.prijem']);
        //print_r($weekSumData);
        return \array_key_exists('group', $weekSumData) ? (float) $weekSumData['group']['value']['value']['income'] : 0.0;
    }

    /**
     * Vytvořit výdajový pokladní doklad.
     *
     * @return bool
     */
    protected function createCashExpense(float $amount, \DateTimeInterface $date): self
    {
        $this->dataReset();
        $day = \AbraFlexi\Functions::dateToFlexiDate($date);
        $this->setData([
            'typPohybuK' => 'typPohybu.vydej', // Výdej
            'datVyst' => $day,
            'datUcto' => $day,
            'popis' => $this->expenseText,
            'sumZklCelkem' => $amount,
            'sumCelkem' => $amount,
            'duzpPuv' => $date->modify('+1 week'),
            'rada' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_ROW')),
            'typDokl' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_TYPE')),
            'pokladna' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_BOX')),
            'stavUzivK' => 'stavUziv.genKasa',
        ]);

        $subitem = [
            'typPolozkyK' => 'typPolozky.ucetni',
            'stavUzivK' => 'stavUziv.genKasa',
            'autogen' => true,
            'datVyst' => $day,
            'cenaMj' => $amount,
            'sumZkl' => $amount,
            'sumCelkem' => $amount,
            'mnozMj' => 1,
            'kopTypUcOp' => true,
            'nazev' => 'výběr z pokladny',
        ];

        $this->addArrayToBranch($subitem);

        if (\Ease\Shared::cfg('ABRAFLEXI_CASH_ACCOUNTING')) {
            $this->setDataValue('typUcOp', \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CASH_ACCOUNTING')));
        }

        $this->sync();

        return $this;
    }

    /**
     * Zkontrolovat, zda již existuje výdajový doklad pro daný týden.
     */
    protected function expenseExistsForWeek(\DateTimeInterface $weekEnd): bool
    {
        $searchDate = $weekEnd->modify('+1 day');

        $expenses = $this->getColumnsFromAbraFlexi(
            ['id', 'kod', 'datVyst', 'popis', 'sumCelkem'],
            [
                'typPohybuK' => 'typPohybu.vydej',
                'datVyst' => $searchDate,
                'popis' => $this->expenseText,
            ],
        );

        return !empty($expenses);
    }
}
