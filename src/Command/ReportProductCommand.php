<?php

namespace App\Command;

use App\Repository\OrderDetailRepository;
use App\Repository\ProductItemRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportProductCommand extends Command
{
    protected static $defaultName = 'report:product';
    protected static $defaultDescription = '(Admin) Export information of product to CSV file.';

    protected const FILE_UPLOAD_PATH = 'http://127.0.0.1:8080/';

    /** @var OrderDetailRepository */
    private $orderDetailRepository;

    /** @var ProductItemRepository */
    private $productItemRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        OrderDetailRepository $orderDetailRepository,
        ProductItemRepository $productItemRepository,
        LoggerInterface $logger
    ) {
        $this->orderDetailRepository = $orderDetailRepository;
        $this->productItemRepository = $productItemRepository;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command allows admin to get product information based on timestamp and specific product.')
            ->addArgument('product_id', InputArgument::OPTIONAL, 'Specific product (product_id)')
            ->addArgument('fromDate', InputArgument::OPTIONAL, 'Date from (yyyy-MM-dd)')
            ->addArgument('toDate', InputArgument::OPTIONAL, 'Date to (yyyy-MM-dd)')
            ->addOption('name', null, InputOption::VALUE_NONE, 'Specific CSV file name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $fromDate = ($input->getArgument('fromDate')) ? substr($input->getArgument('fromDate'), 0, 7) : null;
            $toDate = ($input->getArgument('toDate')) ? substr($input->getArgument('toDate'), 0, 7) : null;

            $dateOrdered = $this->orderDetailRepository->getListDateOrder($input->getArgument('product_id'));
            $monthReport = [];
            $firstHeader = ['', '', '', '', '', '', '', '', '', ''];
            $secondHeader = [
                'No.',
                'Category',
                'Product_item_id',
                'Product',
                'Color',
                'Size',
                'Unit_price',
                'Total_sale_quantity',
                'Total_amount',
                'Quantity_in_stock'
            ];
            foreach ($dateOrdered as $date) {
                $dateConvert = $date->getCreateAt()->format('Y-m');

                if ($fromDate && $fromDate > $dateConvert) {
                    continue;
                }

                if ($toDate && $toDate < $dateConvert) {
                    continue;
                }

                if (!in_array($dateConvert, $firstHeader)) {
                    $monthReport[] = $dateConvert;
                    $firstHeader[] = $dateConvert;
                    $firstHeader[] = $dateConvert;
                    $secondHeader[] = 'Quantity';
                    $secondHeader[] = 'Amount';
                }
            }

            $exportData = [];
            $allProductItem = $this->productItemRepository->getDataForReport($input->getArgument('product_id'));
            if ($allProductItem) {
                $rowNumber = 1;
                foreach ($allProductItem as $productItem) {
                    $totalQuantity = 0;
                    $totalAmount = 0;

                    $productInfo = [];
                    $productInfo['no.'] = $rowNumber;
                    $productInfo['category'] = $productItem->getProduct()->getCategory()->getName();
                    $productInfo['product_item_id'] = $productItem->getId();
                    $productInfo['product_name'] = $productItem->getProduct()->getName();
                    $productInfo['color'] = $productItem->getProduct()->getColor()->getName();
                    $productInfo['size'] = $productItem->getSize()->getValue();
                    $productInfo['unit_price'] = $productItem->getProduct()->getPrice();
                    $productInfo['total_quantity'] = $totalQuantity;
                    $productInfo['total_amount'] = $totalAmount;
                    $productInfo['stock'] = $productItem->getAmount();

                    foreach ($monthReport as $month) {
                        $quantity = 0;
                        $amount = 0;

                        $filterCondition = [
                            ':product_item_id' => $productItem->getId(),
                            ':order_date' => $month . '%'
                        ];
                        $sumData = $this->orderDetailRepository->sumOrderDetailData($filterCondition);
                        if ($sumData) {
                            $quantity = $sumData[0]['sum_quantity'];
                            $amount = $sumData[0]['sum_amount'];
                        }

                        $productInfo['quantity_' . $month] = $quantity;
                        $productInfo['amount_' . $month] = $amount;

                        $totalQuantity += $quantity;
                        $totalAmount += $amount;
                    }

                    $productInfo['total_quantity'] = $totalQuantity;
                    $productInfo['total_amount'] = $totalAmount;

                    $exportData[] = $productInfo;
                    $rowNumber++;
                }
            }

            $fileName = 'Report_Product_' . date('YmdHis') . '.csv';
            if ($input->getOption('name')) {
                $fileName =  $input->getOption('name') . '.csv';
            }

            $outputBuffer = fopen($fileName, 'w');
            fputcsv($outputBuffer, $firstHeader, ',');
            fputcsv($outputBuffer, $secondHeader, ',');

            foreach ($exportData as $row) {
                fputcsv($outputBuffer, $row, ',');
            }
            fclose($outputBuffer);

            $output->write(self::FILE_UPLOAD_PATH . $fileName);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $output->write('Something went wrong! Please contact support.');

        return Command::FAILURE;
    }
}
