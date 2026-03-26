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

use Causal\MfaProtect\Traits\MfaProtectTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentController extends ActionController
{
    use MfaProtectTrait;

    public function __construct(
        private readonly ContentObjectRenderer $contentObjectRenderer
    )
    {}

    public function coverAction(): ResponseInterface
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

        return $this->htmlResponse($html);
    }

    protected function renderActualContent(): string
    {
        $data = $this->request->getAttribute('currentContentObject')->data;
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

    protected function getSettings(): array
    {
        return $this->settings;
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
