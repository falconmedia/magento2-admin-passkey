<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Plugin\Backend\Locale;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\LoginLanguage;
use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Backend\Model\Locale\Resolver;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Locale\OptionInterface as DeployedLocales;

/**
 * Renders the pre-auth Admin login page in a visitor-appropriate language.
 *
 * Only applies while no admin is authenticated (i.e. on the login/forgot pages);
 * a signed-in admin always keeps their own Interface Locale. The allowed set is
 * the deployed Magento/backend locales. In "auto" mode the browser
 * Accept-Language header is matched against that set, with en_US as the fallback
 * when it is deployed.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class LoginLocale
{
    /**
     * @var string[]|null Cached deployed locale codes.
     */
    private ?array $allowed = null;

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly AdminAuthSession $authSession,
        private readonly DeployedLocales $deployedLocales,
        private readonly HttpRequest $request
    ) {
    }

    /**
     * @param Resolver $subject
     * @param string $result
     * @return string
     */
    public function afterGetLocale(Resolver $subject, $result)
    {
        try {
            if (!$this->configProvider->isEnabled() || $this->authSession->isLoggedIn()) {
                return $result;
            }
        } catch (\Throwable) {
            return $result;
        }

        $allowed = $this->allowedLocales();
        if ($allowed === []) {
            return $result;
        }

        $mode = $this->configProvider->getLoginLanguage();
        if ($mode !== '' && $mode !== LoginLanguage::AUTO && in_array($mode, $allowed, true)) {
            return $mode;
        }

        $matched = $this->matchBrowser($allowed);
        if ($matched !== null) {
            return $matched;
        }

        return in_array('en_US', $allowed, true) ? 'en_US' : $result;
    }

    /**
     * Deployed backend locale codes.
     *
     * @return string[]
     */
    private function allowedLocales(): array
    {
        if ($this->allowed === null) {
            $this->allowed = [];
            try {
                foreach ($this->deployedLocales->getOptionLocales() as $option) {
                    if (isset($option['value']) && $option['value'] !== '') {
                        $this->allowed[] = (string) $option['value'];
                    }
                }
            } catch (\Throwable) {
                $this->allowed = [];
            }
        }

        return $this->allowed;
    }

    /**
     * Best deployed locale for the browser Accept-Language header, or null.
     *
     * @param string[] $allowed
     * @return string|null
     */
    private function matchBrowser(array $allowed): ?string
    {
        $header = (string) $this->request->getServer('HTTP_ACCEPT_LANGUAGE', '');
        if ($header === '') {
            return null;
        }

        // language prefix -> first matching deployed locale (e.g. 'nl' -> 'nl_NL').
        $byLanguage = [];
        foreach ($allowed as $locale) {
            $lang = strtolower(substr($locale, 0, 2));
            if (!isset($byLanguage[$lang])) {
                $byLanguage[$lang] = $locale;
            }
        }

        $ranges = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $quality = 1.0;
            $tag = $part;
            if (str_contains($part, ';')) {
                [$tag, $params] = array_map('trim', explode(';', $part, 2));
                if (preg_match('/q=([0-9.]+)/', $params, $m)) {
                    $quality = (float) $m[1];
                }
            }
            $ranges[] = [strtolower(trim($tag)), $quality];
        }

        usort($ranges, static fn(array $a, array $b): int => $b[1] <=> $a[1]);

        foreach ($ranges as [$tag]) {
            if ($tag === '' || $tag === '*') {
                continue;
            }
            $normalized = str_replace('-', '_', $tag);
            foreach ($allowed as $locale) {
                if (strtolower($locale) === $normalized) {
                    return $locale;
                }
            }
            $language = substr($tag, 0, 2);
            if (isset($byLanguage[$language])) {
                return $byLanguage[$language];
            }
        }

        return null;
    }
}
