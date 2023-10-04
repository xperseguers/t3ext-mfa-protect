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

class ContentContentObject
{
    public function modifyDBRow(array &$row, string $table): void
    {
        if ($table !== 'tt_content' || !(bool)$row['tx_mfaprotect_enable']) {
            return;
        }

        // TODO: check if MFA is fresh enough

        // If not: replace content element with our protection plugin
        $row['header_layout'] = 100;    // hidden
        $row['CType'] = 'list';
        $row['list_type'] = 'mfaprotect_overlay';
    }
}
