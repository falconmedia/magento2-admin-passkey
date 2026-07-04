<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\Passkey;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Renders the current admin's passkey list and wires the management actions.
 *
 * Only the authenticated admin's own credentials are ever listed, and the
 * rename/revoke actions target the self-service endpoints. The interactive
 * behaviour is delegated to a CSP-safe JS component via data-mage-init.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Index extends Template
{
    /**
     * JS component (RequireJS module) that wires the management actions.
     */
    private const JS_COMPONENT = 'FalconMedia_AdminPasskey/js/passkey-manager';

    /**
     * @var CredentialInterface[]|null
     */
    private ?array $credentials = null;

    public function __construct(
        Context $context,
        private readonly AdminSession $adminSession,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly FormKey $formKeyProvider,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * The current admin's passkeys, newest first.
     *
     * @return CredentialInterface[]
     */
    public function getCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $adminUserId = $this->getCurrentAdminUserId();
        if ($adminUserId <= 0) {
            $this->credentials = [];

            return $this->credentials;
        }

        $sortOrder = $this->sortOrderBuilder
            ->setField(CredentialInterface::CREATED_AT)
            ->setDirection('DESC')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(CredentialInterface::ADMIN_USER_ID, $adminUserId)
            ->addSortOrder($sortOrder)
            ->create();

        $this->credentials = $this->credentialRepository->getList($searchCriteria)->getItems();

        return $this->credentials;
    }

    /**
     * Whether the current admin has any passkeys to display.
     *
     * @return bool
     */
    public function hasCredentials(): bool
    {
        return $this->getCredentials() !== [];
    }

    /**
     * Whether the given credential is active (and therefore actionable).
     *
     * @param CredentialInterface $credential
     * @return bool
     */
    public function isActive(CredentialInterface $credential): bool
    {
        return $credential->getStatus() === CredentialInterface::STATUS_ACTIVE;
    }

    /**
     * Human-readable status label for a credential.
     *
     * @param CredentialInterface $credential
     * @return \Magento\Framework\Phrase
     */
    public function getStatusLabel(CredentialInterface $credential): \Magento\Framework\Phrase
    {
        return $this->isActive($credential) ? __('Active') : __('Revoked');
    }

    /**
     * Display name for a credential, falling back to a generic label.
     *
     * @param CredentialInterface $credential
     * @return string
     */
    public function getDisplayName(CredentialInterface $credential): string
    {
        $friendlyName = (string) $credential->getFriendlyName();

        return $friendlyName !== '' ? $friendlyName : (string) __('Unnamed passkey');
    }

    /**
     * Non-sensitive, human-readable device summary for a credential.
     *
     * @param CredentialInterface $credential
     * @return string
     */
    public function getDeviceSummary(CredentialInterface $credential): string
    {
        $raw = (string) $credential->getDeviceMetadata();
        if ($raw === '') {
            return (string) __('Unknown device');
        }

        try {
            $decoded = $this->json->unserialize($raw);
        } catch (\InvalidArgumentException) {
            return (string) __('Unknown device');
        }

        if (!is_array($decoded)) {
            return (string) __('Unknown device');
        }

        $format = isset($decoded['attestation_format']) ? (string) $decoded['attestation_format'] : '';
        $aaguid = isset($decoded['aaguid']) ? (string) $decoded['aaguid'] : '';

        $parts = [];
        if ($format !== '') {
            $parts[] = (string) __('Attestation: %1', $format);
        }
        if ($aaguid !== '') {
            $parts[] = (string) __('AAGUID: %1', $aaguid);
        }

        return $parts === [] ? (string) __('Unknown device') : implode(' · ', $parts);
    }

    /**
     * URL of the setup wizard used by the "add passkey" action.
     *
     * @return string
     */
    public function getWizardUrl(): string
    {
        return $this->getUrl('adminpasskey/passkey/wizard');
    }

    /**
     * data-mage-init JSON binding the manager component to its configuration.
     *
     * @return string
     */
    public function getMageInitJson(): string
    {
        return $this->json->serialize([
            self::JS_COMPONENT => [
                'renameUrl' => $this->getUrl('adminpasskey/passkey/rename'),
                'revokeUrl' => $this->getUrl('adminpasskey/passkey/revoke'),
                'formKey' => $this->formKeyProvider->getFormKey(),
                'labels' => [
                    'renamePrompt' => (string) __('Enter a new name for this passkey:'),
                    'revokeConfirm' => (string) __('Revoke this passkey? This cannot be undone.'),
                    'failed' => (string) __('The action could not be completed. Please try again.'),
                ],
            ],
        ]);
    }

    /**
     * Resolve the current admin user id.
     *
     * @return int
     */
    private function getCurrentAdminUserId(): int
    {
        return (int) $this->adminSession->getUser()?->getId();
    }
}
