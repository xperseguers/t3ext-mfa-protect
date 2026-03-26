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

namespace Causal\MfaProtect\EventListener;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;

class FrontendContentObjectRecordsEventListener
{
    public function modifyDBRow(ModifyRecordsAfterFetchingContentEvent $event): void
    {
        if ($event->getConfiguration()['table'] !== 'tt_content') {
            return;
        }

        $records = $event->getRecords();
        foreach ($records as $i => $record) {
            if (!(bool)$record['tx_mfaprotect_enable']) {
                continue;
            }

            // Systematically replace content element with our protection plugin wrapper
            $record['header_layout'] = 100;    // hidden
            $record['CType'] = 'mfaprotect_content';
            $record['tstamp'] = $GLOBALS['EXEC_TIME'];

            $records[$i] = $record;
        }
        $event->setRecords($records);

        // Ensure TYPO3 does not cache the output!
        /** @var ServerRequest $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        /** @var CacheInstruction $frontendCacheInstruction */
        $frontendCacheInstruction = $request->getAttribute('frontend.cache.instruction');
        $frontendCacheInstruction->disableCache('EXT:mfa_protect: MFA protection content element detected');
    }
}
