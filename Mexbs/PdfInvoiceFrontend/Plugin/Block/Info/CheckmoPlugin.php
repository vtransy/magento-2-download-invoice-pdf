<?php

namespace Mexbs\PdfInvoiceFrontend\Plugin\Block\Info;
/**
 * Class CheckmoPlugin
 * @package Namespace\Module\Plugin\Block\Info
 */
class CheckmoPlugin
{
    /**
     * @param \Magento\OfflinePayments\Block\Info\Checkmo $subject
     * @param callable $proceed
     * @return \Magento\Framework\Phrase
     */
    public function aroundToPdf(\Magento\OfflinePayments\Block\Info\Checkmo $subject, callable $proceed)
    {
        return __('Check / Money order');
    }
}