<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\MfaProtect\Traits;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

trait MfaProtectTrait
{
    protected static int $instances = 0;

    protected static int $tokenValidity = 0;

    protected function checkNewMfaToken(): bool
    {
        if ($this->getRequest()->getMethod() === 'POST' && static::$instances === 1) {
            $oneTimePassword = $this->getRequest()->getParsedBody()['totp'] ?? '';

            if (preg_match('/^[0-9]{6}$/', $oneTimePassword)) {
                if (ExtensionManagementUtility::isLoaded('mfa_frontend')) {
                    $user = $this->getFrontendUserAuthentication()->user;

                    $mfa = json_decode($user['mfa_frontend'] ?? '', true) ?? [];
                    $mfaEnabled = $mfa['totp']['active'] ?? false;
                    $secret = $mfa['totp']['secret'] ?? '';

                    if ($mfaEnabled) {
                        $totp = GeneralUtility::makeInstance(Totp::class, $secret, 'sha1');
                        if ($totp->verifyTotp($oneTimePassword) === true) {
                            // Store last usage of TOTP
                            $mfa['totp']['lastUsed'] = $GLOBALS['EXEC_TIME'];
                            // Reset failed attempts
                            $mfa['totp']['attempts'] = 0;
                            $mfa['totp']['updated'] = $GLOBALS['EXEC_TIME'];
                            $this->persistMfa($mfa);

                            // Store last check time
                            $this->getFrontendUserAuthentication()->setSessionData('mfa_protect.time', $GLOBALS['EXEC_TIME']);

                            // TODO: shall we redirect instead in order to prevent serving from a POST request?
                            return true;
                        } else {
                            // Increase failed attempts
                            $mfa['totp']['attempts']++;
                            $mfa['totp']['updated'] = $GLOBALS['EXEC_TIME'];
                            $this->persistMfa($mfa);
                        }
                    }
                } else {
                    throw new \RuntimeException('Sorry, we currently don\'t know how to validate your MFA token', 1696525584);
                }
            }
        }

        return false;
    }

    /**
     * BEWARE: this method is only supposed to be called if EXT:mfa_frontend is loaded.
     *
     * @param array $mfa
     */
    protected function persistMfa(array $mfa): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users')
            ->update(
                'fe_users',
                [
                    'mfa_frontend' => json_encode($mfa),
                ],
                [
                    'uid' => $this->getFrontendUserAuthentication()->user['uid'],
                ]
            );
    }

    protected function isMfaTokenRecent(): bool
    {
        $tokenValidity = $this->getTokenValidity();
        $lastCheck = $this->getFrontendUserAuthentication()->getSessionData('mfa_protect.time') ?: 0;

        // Take advantage of the latest login time if EXT:mfa_frontend is loaded
        if (ExtensionManagementUtility::isLoaded('mfa_frontend')) {
            $user = $this->getFrontendUserAuthentication()->user;
            $mfa = json_decode($user['mfa_frontend'] ?? '', true) ?? [];
            $lastUsed = $mfa['totp']['lastUsed'] ?? 0;
            if ($lastUsed > $lastCheck) {
                $lastCheck = $lastUsed;
            }
        }

        return $lastCheck >= $tokenValidity;
    }

    protected function getAvailableMfaProviders(): array
    {
        $user = $this->getFrontendUserAuthentication()->user;

        if (ExtensionManagementUtility::isLoaded('mfa_frontend')) {
            // TODO: add support for any kind of MFA
            $mfa = json_decode($user['mfa_frontend'] ?? '', true) ?? [];
            $hasTotp = $mfa['totp']['active'] ?? false;
        } else {
            // NOTE: This part is not supposed to be working until that bug gets fixed:
            //       https://forge.typo3.org/issues/102081
            $mfa = json_decode($user['mfa'] ?? '', true) ?? [];
            $hasTotp = $mfa['totp']['active'] ?? false;
        }

        return $hasTotp ? ['totp'] : [];
    }

    protected function getTokenValidity(): int
    {
        if (static::$tokenValidity > 0) {
            return static::$tokenValidity;
        }

        $pluginSettings = $this->getSettings();

        $tsKey = 'tokenValidity';
        $tokenValidity = $pluginSettings[$tsKey] ?? null;
        if (is_array($tokenValidity)) {
            // Dynamic value
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $pluginSettings = $typoScriptService->convertPlainArrayToTypoScriptArray($pluginSettings);
        }
        if (array_key_exists($tsKey . '.', $pluginSettings)) {
            $tokenValidity = $this->contentObjectRenderer->cObjGetSingle(
                $pluginSettings[$tsKey] ?? '',
                    $pluginSettings[$tsKey . '.'] ?? []
            );
        }

        $tokenValidity = max(0, (int)$tokenValidity);
        static::$tokenValidity = $GLOBALS['EXEC_TIME'] - 60 * $tokenValidity;
        return static::$tokenValidity;
    }

    protected function getFrontendUserAuthentication(): FrontendUserAuthentication
    {
        return $this->getRequest()->getAttribute('frontend.user');
    }

    abstract protected function getSettings(): array;

    abstract protected function getRequest(): ServerRequestInterface;
}
