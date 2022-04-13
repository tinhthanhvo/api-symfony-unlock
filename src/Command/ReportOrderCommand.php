<?php

namespace App\Command;

use App\Repository\PurchaseOrderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportOrderCommand extends Command
{
    protected static $defaultName = 'report:purchase-order';
    protected static $defaultDescription = '(Admin) Export information of order to CSV file.';

    protected const FILE_UPLOAD_PATH = 'http://127.0.0.1:8080/';

    /** @var PurchaseOrderRepository */
    private $purchaseOrderRepository;

    /** @var LoggerInterface */
    private $logger;

    private const STATUS_DEFAULT = [
        1 => 'PENDING',
        2 => 'APPROVED',
        3 => 'CANCELED',
        4 => 'COMPLETED'
    ];

    public function __construct(
        PurchaseOrderRepository $purchaseOrderRepository,
        LoggerInterface $logger
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command allows admin to get order information based on timestamp and status.')
            ->addArgument('status', InputArgument::OPTIONAL, 'Status of order')
            ->addArgument('fromDate', InputArgument::OPTIONAL, 'Date from (yyyy-MM-dd)')
            ->addArgument('toDate', InputArgument::OPTIONAL, 'Date to (yyyy-MM-dd)')
            ->addOption('name', null, InputOption::VALUE_NONE, 'Specific CSV file name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $arguments = [];
            $arguments['status'] = $input->getArgument('status');
            $arguments['fromDate'] = $input->getArgument('fromDate');
            $arguments['toDate'] = $input->getArgument('toDate');
            $listOrderPurchase = $this->purchaseOrderRepository->getDataForReport($arguments);

            $orderExportData = [];
            if ($listOrderPurchase) {
                foreach ($listOrderPurchase as $order) {
                    $orderItems = $order->getOrderItems();
                    foreach ($orderItems as $item) {
                        $orderItem = [];
                        $orderItem['id'] = $order->getId();
                        $orderItem['date_order'] = $order->getCreateAt()->format('Y-m-d H:i:s');
                        $orderItem['customer_name'] = $order->getRecipientName();
                        $orderItem['phone'] = $order->getRecipientPhone();
                        $orderItem['email'] = $order->getRecipientEmail();
                        $orderItem['address'] = $order->getAddressDelivery();

                        $orderItem['category'] = $item->getProductItem()->getProduct()->getCategory()->getName();
                        $orderItem['product_item_id'] = $item->getProductItem()->getId();
                        $orderItem['product'] = $item->getProductItem()->getProduct()->getName();
                        $orderItem['color'] = $item->getProductItem()->getProduct()->getColor()->getName();
                        $orderItem['size'] = $item->getProductItem()->getSize()->getValue();

                        $orderItem['unit_price'] = $item->getProductItem()->getProduct()->getPrice();
                        $orderItem['amount'] = $item->getAmount();
                        $orderItem['total_price'] = $item->getPrice();
                        $orderItem['status'] = self::STATUS_DEFAULT[$order->getStatus()];

                        $orderExportData[] = $orderItem;
                    }
                }
            }

            $fileName = 'Report_Order_' . date('YmdHis') . '.csv';
            if ($input->getOption('name')) {
                $fileName =  $input->getOption('name') . '.csv';
            }

            $outputBuffer = fopen($fileName, 'w');
            fputcsv($outputBuffer, [
                'Order ID',
                'Order date',
                'Customer',
                'Phone',
                'Email',
                'Address',
                'Category',
                'Product item ID',
                'Product',
                'Color',
                'Size',
                'Unit price',
                'Amount',
                'Total price',
                'Status'
            ], ',');

            foreach ($orderExportData as $row) {
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
