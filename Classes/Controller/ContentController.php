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

use CodeFareith\CfGoogleAuthenticator\Utility\GoogleAuthenticatorUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
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

    public function __construct(ContentObjectRenderer $contentObjectRenderer)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }

    public function coverAction(): ResponseInterface
    {
        static::$instances++;

        if ($this->isMfaTokenRecent()) {
            $html = $this->renderActualContent();
        } else {
            $this->view->assign('firstOnPage', static::$instances === 1);
            $html = $this->view->render();
        }

        return $this->htmlResponse($html);
    }

    protected function isMfaTokenRecent(): bool
    {
        if ($this->request->getMethod() === 'POST' && static::$instances === 1) {
            $otp = $this->request->getParsedBody()['tx_mfaprotect_otp'] ?? '';
            if (preg_match('/^[0-9]{6}$/', $otp)) {
                if (ExtensionManagementUtility::isLoaded('cf_google_authenticator')) {
                    $user = $this->getFrontendUserAuthentication()->user;
                    $mfaEnabled = (bool)$user['tx_cfgoogleauthenticator_enabled'];
                    $secret = $user['tx_cfgoogleauthenticator_secret'];

                    if ($mfaEnabled && GoogleAuthenticatorUtility::verifyOneTimePassword($secret, $otp) === true) {
                        $this->getFrontendUserAuthentication()->setSessionData('mfa_protect.time', $GLOBALS['EXEC_TIME']);

                        // TODO: shall we redirect instead in order to prevent serving from a POST request?
                        return true;
                    }
                } else {
                    throw new \RuntimeException('Sorry, we currently don\'t know how to validate your MFA token', 1696525584);
                }
            }
        }

        $tokenValidity = $this->getTokenValidity();
        $lastCheck = $this->getFrontendUserAuthentication()->getSessionData('mfa_protect.time') ?: 0;

        return $lastCheck >= $tokenValidity;
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
        return $this->request->getAttribute('frontend.user');
    }
}
