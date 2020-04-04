<?php

namespace Mexbs\PdfInvoiceFrontend\Controller\Invoice;

use Magento\Framework\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdf;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class PdfDownload extends Action\Action
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var InvoicePdf
     */
    private $invoicePdf;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * PdfDownload constructor.
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param AppState $appState
     * @param InvoicePdf $invoicePdf
     * @param DateTime $dateTime
     * @param InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        AppState $appState,
        InvoicePdf $invoicePdf,
        DateTime $dateTime,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        InvoiceRepositoryInterface $invoiceRepository
    )
    {
        $this->customerSession = $customerSession;
        $this->fileFactory = $fileFactory;
        $this->appState = $appState;
        $this->invoicePdf = $invoicePdf;
        $this->dateTime = $dateTime;
        $this->invoiceRepository = $invoiceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }


    /**
     * Generate pdf invoice for download
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Zend_Pdf_Exception
     */
    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        if (!$orderId) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('sales/order/history');
            return $resultRedirect;
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();
        try {
            $invoices = $this->invoiceRepository->getList($searchCriteria);
            $invoiceRecords = $invoices->getItems();
        } catch (Exception $exception)  {
            $this->logger->critical($exception->getMessage());
            $invoiceRecords = null;
        }

        if (!$invoiceRecords) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('sales/order/history');
            return $resultRedirect;
        }

        $order = $this->orderRepository->get($orderId);
        $customerId = $this->customerSession->getCustomerId();

        if ($customerId && $order->getId() && $order->getCustomerId() && $order->getCustomerId() == $customerId) {
            foreach ($invoices as $invoice) {
                $pdf = $this->invoicePdf->getPdf([$invoice]);
                $date = $this->dateTime->date('Y-m-d_H-i-s');
                return $this->fileFactory->create(
                    'invoice' . $date . '.pdf',
                    $pdf->render(),
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
                break;  // download first invoice
            }
        } else {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('sales/order/history');
            return $resultRedirect;
        }
    }
}
