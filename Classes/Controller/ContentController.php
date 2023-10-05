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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentController extends ActionController
{
    protected ContentObjectRenderer $contentObjectRenderer;

    public function __construct(ContentObjectRenderer $contentObjectRenderer)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }

    public function coverAction(): ResponseInterface
    {
        if ($this->isMfaTokenRecent()) {
            $html = $this->renderActualContent();
        } else {
            $html = $this->view->render();
        }

        return $this->htmlResponse($html);
    }

    protected function isMfaTokenRecent(): bool
    {
        if ($this->request->getMethod() === 'POST') {
            $otp = $this->request->getParsedBody()['tx_mfaprotect_otp'] ?? '';
            if (preg_match('/^[0-9]{6}$/', $otp)) {
                // TODO: check OTP and store as used recently
                if ($otp === '123456') {
                    // TODO: shall we redirect instead in order to prevent serving from a POST request?
                    return true;
                }
            }
        }

        // TODO: check if MFA token is recent enough
        return false;
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
}
