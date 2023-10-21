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

namespace Causal\MfaProtect\Hooks;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentContentObject
{
    public function modifyDBRow(array &$row, string $table): void
    {
        if ($table !== 'tt_content' || !(bool)$row['tx_mfaprotect_enable']) {
            return;
        }

        // Systematically replace content element with our protection plugin wrapper
        $row['header_layout'] = 100;    // hidden
        $row['CType'] = 'list';
        $row['list_type'] = 'mfaprotect_content';
        $row['tstamp'] = $GLOBALS['EXEC_TIME'];

        // Ensure TYPO3 does not cache the output!
        /** @var ServerRequest $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if (version_compare($typo3Version->getBranch(), '11.5', '>=')) {
            $request->getAttribute('frontend.controller')->no_cache = true;
        } else {
            // TYPO3 v10
            $GLOBALS['TSFE']->no_cache = true;
        }
    }
}
