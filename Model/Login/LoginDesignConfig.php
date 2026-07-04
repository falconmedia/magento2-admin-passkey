<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Login;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\LoginDesignLayout;

/**
 * Typed accessors for the active login-page design copy.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class LoginDesignConfig
{
    public function __construct(
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function getLayout(): string
    {
        return $this->configProvider->getLoginDesignLayout();
    }

    public function isSpotlight(): bool
    {
        return $this->getLayout() === LoginDesignLayout::SPOTLIGHT;
    }

    public function isSplitConsole(): bool
    {
        return $this->getLayout() === LoginDesignLayout::SPLIT_CONSOLE;
    }

    public function isCommandDeck(): bool
    {
        return $this->getLayout() === LoginDesignLayout::COMMAND_DECK;
    }

    public function getEnvironmentBadge(): string
    {
        return $this->configProvider->getLoginSpotlightEnvironmentBadge();
    }

    public function getPasskeyHeadline(): string
    {
        return $this->configProvider->getLoginSpotlightPasskeyHeadline();
    }

    public function getPasskeyDescription(): string
    {
        return $this->configProvider->getLoginSpotlightPasskeyDescription();
    }

    public function getPasskeyButtonLabel(): string
    {
        if ($this->isSpotlight()) {
            return $this->configProvider->getLoginSpotlightPasskeyButtonLabel();
        }

        return (string) __('Sign in with a passkey');
    }

    public function getPasswordTwoFaNotice(): string
    {
        return match ($this->getLayout()) {
            LoginDesignLayout::SPLIT_CONSOLE => $this->configProvider->getLoginSplitPasswordTwoFaNotice(),
            LoginDesignLayout::COMMAND_DECK => $this->configProvider->getLoginCommandPasswordTwoFaNotice(),
            default => $this->configProvider->getLoginSpotlightPasswordTwoFaNotice(),
        };
    }

    public function getSplitBrandHeadline(): string
    {
        return $this->configProvider->getLoginSplitBrandHeadline();
    }

    public function getSplitBrandHighlight(): string
    {
        return $this->configProvider->getLoginSplitBrandHighlight();
    }

    public function getSplitBrandDescription(): string
    {
        return $this->configProvider->getLoginSplitBrandDescription();
    }

    public function getSplitSignInTitle(): string
    {
        return $this->configProvider->getLoginSplitSignInTitle();
    }

    public function getSplitSignInSubtitle(): string
    {
        return $this->configProvider->getLoginSplitSignInSubtitle();
    }

    public function getSplitPasskeySubtitle(): string
    {
        return $this->configProvider->getLoginSplitPasskeySubtitle();
    }

    public function getCommandStageHeadline(): string
    {
        return $this->configProvider->getLoginCommandStageHeadline();
    }

    public function getCommandStageDescription(): string
    {
        return $this->configProvider->getLoginCommandStageDescription();
    }

    public function getCommandAuthLabel(): string
    {
        return $this->configProvider->getLoginCommandAuthLabel();
    }

    public function getCommandSignInTitle(): string
    {
        return $this->configProvider->getLoginCommandSignInTitle();
    }

    public function getCommandPasskeySubtitle(): string
    {
        return $this->configProvider->getLoginCommandPasskeySubtitle();
    }

    public function getCommandFooterText(): string
    {
        return $this->configProvider->getLoginCommandFooterText();
    }
}
