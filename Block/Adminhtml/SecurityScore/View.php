<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\SecurityScore;

use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreResult;
use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreServiceInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Renders the current security score, category breakdown and recommendations.
 */
class View extends Template
{
    /**
     * @var SecurityScoreResult|null
     */
    private ?SecurityScoreResult $result = null;

    public function __construct(
        Context $context,
        private readonly SecurityScoreServiceInterface $securityScoreService,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Compute (once) and return the current score result.
     *
     * @return SecurityScoreResult
     */
    public function getResult(): SecurityScoreResult
    {
        if ($this->result === null) {
            $this->result = $this->securityScoreService->compute();
        }

        return $this->result;
    }

    /**
     * URL for the "Recalculate & Snapshot" action.
     *
     * @return string
     */
    public function getSnapshotUrl(): string
    {
        return $this->getUrl('adminpasskey/securityscore/snapshot');
    }

    /**
     * Map a score to a colour band CSS class.
     *
     * @param int $score
     * @return string
     */
    public function getScoreClass(int $score): string
    {
        return match (true) {
            $score >= 85 => 'grid-severity-notice',
            $score >= 50 => 'grid-severity-minor',
            default => 'grid-severity-critical',
        };
    }
}
