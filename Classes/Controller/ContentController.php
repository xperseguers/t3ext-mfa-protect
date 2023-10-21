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

namespace Causal\MfaProtect\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentController extends ActionController
{
    protected static int $instances = 0;
    protected static int $tokenValidity = 0;

    protected ContentObjectRenderer $contentObjectRenderer;

    protected string $typo3Branch;

    public function __construct(ContentObjectRenderer $contentObjectRenderer)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
        $this->typo3Branch = (new Typo3Version())->getBranch();
    }

    public function coverAction()
    {
        static::$instances++;

        $validTokenProvided = $this->checkNewMfaToken();

        if ($validTokenProvided || $this->isMfaTokenRecent()) {
            $html = $this->renderActualContent();
        } else {
            $availableMfaProviders = $this->getAvailableMfaProviders();
            $isFirstOnPage = static::$instances === 1;
            $this->view->assignMultiple([
                'availableMfaProviders' => $availableMfaProviders,
                'isFirstOnPage' => $isFirstOnPage,
            ]);
            $html = $this->view->render();
        }

        if (version_compare($this->typo3Branch, '11.5', '<')) {
            return $html;
        }
        return $this->htmlResponse($html);
    }

    protected function checkNewMfaToken(): bool
    {
        if ($this->request->getMethod() === 'POST' && static::$instances === 1) {
            if (version_compare($this->typo3Branch, '11.5', '>=')) {
                $oneTimePassword = $this->request->getParsedBody()['totp'] ?? '';
            } else {
                $oneTimePassword = GeneralUtility::_POST()['totp'] ?? '';
            }

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

                            // Store last check time (note: we could rely on $mfa['totp']['lastUsed'] instead/as well
                            // in that very context (using EXT:mfa_frontend)
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
     * BEWARE: this method is not supposed to be called if EXT:mfa_frontend is loaded.
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
        // Note: with EXT:mfa_frontend, we could rely on $mfa['totp']['lastUsed'] instead/as well
        //       which would have the advantage of taking latest login into account
        $lastCheck = $this->getFrontendUserAuthentication()->getSessionData('mfa_protect.time') ?: 0;

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

    protected function renderActualContent(): string
    {
        $data = $this->configurationManager->getContentObject()->data;
        $row = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->select(
                ['*'],
                'tt_content',
                [
                    'uid' => $data['uid']
                ]
            )
            ->fetchAssociative();
        $this->contentObjectRenderer->start($row, 'tt_content', $this->request);
        return $this->contentObjectRenderer->cObjGetSingle('< tt_content', []);
    }

    protected function getTokenValidity(): int
    {
        if (static::$tokenValidity > 0) {
            return static::$tokenValidity;
        }

        $tsKey = 'tokenValidity';
        $tokenValidity = $this->settings[$tsKey] ?? null;
        if (is_array($tokenValidity)) {
            // Dynamic value
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $settings = $typoScriptService->convertPlainArrayToTypoScriptArray($this->settings);
            $tokenValidity = $this->contentObjectRenderer->cObjGetSingle(
                $settings[$tsKey] ?? '',
                $settings[$tsKey . '.'] ?? []
            );
        }

        $tokenValidity = max(0, (int)$tokenValidity);
        static::$tokenValidity = $GLOBALS['EXEC_TIME'] - 60 * $tokenValidity;
        return static::$tokenValidity;
    }

    protected function getFrontendUserAuthentication(): FrontendUserAuthentication
    {
        if (version_compare($this->typo3Branch, '11.5', '<')) {
            return $GLOBALS['TSFE']->fe_user;
        }

        return $this->request->getAttribute('frontend.user');
    }
}
