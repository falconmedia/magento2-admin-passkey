<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Email;

use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\Store;

/**
 * Default {@see BrandedEmailSenderInterface} implementation built on the native
 * TransportBuilder. Suspends inline translation while sending and merges the
 * shared branding variables into every message.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class BrandedEmailSender implements BrandedEmailSenderInterface
{
    /**
     * Email identity used as the sender scope.
     */
    private const EMAIL_SENDER = 'general';

    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly BrandedEmailVariables $brandedEmailVariables
    ) {
    }

    /**
     * @inheritdoc
     */
    public function send(string $templateId, string $toEmail, string $toName, array $vars = [], ?int $storeId = null): void
    {
        $scopeStoreId = $storeId ?? Store::DEFAULT_STORE_ID;

        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => $scopeStoreId])
                ->setTemplateVars($this->brandedEmailVariables->build($vars, $storeId))
                ->setFromByScope(self::EMAIL_SENDER, $scopeStoreId)
                ->addTo($toEmail, $toName)
                ->getTransport();
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
