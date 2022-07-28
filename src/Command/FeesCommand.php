<?php

namespace App\Command;

use App\Infra\Client\ApiClient;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fees',
    description: 'Calculate fees for input transactions list',
)]
class FeesCommand extends Command
{
    protected static array $euCountriesList = [
        'AT',
        'BE',
        'BG',
        'CY',
        'CZ',
        'DE',
        'DK',
        'EE',
        'ES',
        'FI',
        'FR',
        'GR',
        'HR',
        'HU',
        'IE',
        'IT',
        'LT',
        'LU',
        'LV',
        'MT',
        'NL',
        'PO',
        'PT',
        'RO',
        'SE',
        'SI',
        'SK',
        ];

    /**
     * @param string $currencies
     * @param string $currency
     * @return mixed
     */
    protected static function currencyRate(string $currencies, string $currency): mixed
    {
        return @json_decode($currencies, true)['rates'][$currency];
    }

    /**
     * @param string $binListApiProvider
     * @param string $transactionBin
     * @return bool
     * @throws \Exception
     */
    protected static function isEu(string $binListApiProvider, string $transactionBin): bool
    {
        $binResults = ApiClient::getResourceByPath($binListApiProvider, $transactionBin);
        if (!$binResults) {
            throw new \InvalidArgumentException('No such bin provided!');
        }

        $r = json_decode($binResults);
        return in_array($r->country->alpha2, self::$euCountriesList);
    }

    /**
     * @param string $file
     * @param string $binListApiProvider
     * @param string $exchangeRatesApiProvider
     * @param string $apiKey
     *
     * @return array
     *
     * @throws GuzzleException
     */
    protected function calculate(
        string $file,
        string $binListApiProvider,
        string $exchangeRatesApiProvider,
        string $apiKey
    ): array {
        $fees = [];
        $binErrors = [];
        $currencies = ApiClient::getResourceByUri($exchangeRatesApiProvider, ['apikey' => $apiKey]);

        foreach (explode("\n", file_get_contents($file)) as $row) {
            if (empty($row)) return [];

            $transaction = json_decode($row);

            try {
                $isEu = self::isEu($binListApiProvider, $transaction->bin);
            } catch (\Throwable $e) {
                $binErrors[] = $transaction->bin;
                continue;
            }

            $rate = self::currencyRate($currencies, $transaction->currency);
            $amountFixed = ($transaction->currency == 'EUR' or $rate == 0)
                ? $transaction->amount
                : $transaction->amount / $rate;

            $fees[] = round($amountFixed * ($isEu ? 0.01 : 0.02), 2);
        }

        return [$fees, $binErrors];
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the input transaction list')
        ;
    }

    /**
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        if (!$file) {
            $io->error('You should specify an transactions list filename!');
        }

        $io->note(sprintf('You passed an transactions list filename: `%s`', $file));

        $result = $this->calculate(
            $file,
            'https://lookup.binlist.net',
            'https://api.apilayer.com/exchangerates_data/latest',
            '2skUoCGBACbLdsNYg8eJpBITJQRfqcBx'
        );

        $io->success(sprintf('Fee rates calculated successfully for %s transactions.', count($result[0])));

        if (count($result[1])) {
            $io->error(sprintf('No such bin found for %s transactions.', count($result[1])));
        }

        $io->writeln($result[0]);

        return Command::SUCCESS;
    }
}
