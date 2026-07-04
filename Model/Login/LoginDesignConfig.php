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
 * Shared copy (headline, sign-in title/subtitle, passkey subtitle, button label,
 * 2FA notice, environment badge) lives on the parent login_design group and is
 * used by every layout. Only brand rail (Split Console) and stage/footer
 * (Command Deck) copy is layout-specific.
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

    // --- Shared copy (all layouts) ------------------------------------------

    public function getEnvironmentBadge(): string
    {
        return $this->configProvider->getLoginEnvironmentBadge();
    }

    public function getPasskeyHeadline(): string
    {
        return $this->configProvider->getLoginPasskeyHeadline();
    }

    public function getPasskeyDescription(): string
    {
        return $this->configProvider->getLoginPasskeyDescription();
    }

    public function getPasskeyButtonLabel(): string
    {
        return $this->configProvider->getLoginPasskeyButtonLabel();
    }

    public function getSignInTitle(): string
    {
        return $this->configProvider->getLoginSignInTitle();
    }

    public function getSignInSubtitle(): string
    {
        return $this->configProvider->getLoginSignInSubtitle();
    }

    public function getPasskeySubtitle(): string
    {
        return $this->configProvider->getLoginPasskeySubtitle();
    }

    public function getPasswordTwoFaNotice(): string
    {
        return $this->configProvider->getLoginPasswordTwoFaNotice();
    }

    // --- Split Console specific ---------------------------------------------

    public function getSplitBrandHeadline(): string
    {
        return $this->configProvider->getLoginSplitBrandHeadline();
    }

    // --- Command Deck specific ----------------------------------------------

    public function getCommandAuthLabel(): string
    {
        return $this->configProvider->getLoginCommandAuthLabel();
    }

    public function getCommandFooterText(): string
    {
        return $this->configProvider->getLoginCommandFooterText();
    }
}
