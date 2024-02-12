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

use TYPO3\CMS\Backend\Utility\BackendUtility;

class DataHandler
{
    /**
     * Hooks into \TYPO3\CMS\Core\DataHandling\DataHandler before records get actually saved to the database.
     *
     * @param string $operation
     * @param string $table
     * @param int|string $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_postProcessFieldArray(
        string $operation,
        string $table,
        $id,
        array &$fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
    ): void
    {
        if ($table !== 'tt_content') {
            return;
        }

        if ($operation === 'update') {
            $record = BackendUtility::getRecord($table, $id);
            $record = array_merge($record, $fieldArray);
        } else {
            // 'new'
            $record = $fieldArray;
        }

        // If the usergroup access restriction is set to "Hide at login"
        // the MFA protection cannot be used
        if ($record['fe_group'] === '-1') {
            $fieldArray['tx_mfaprotect_enable'] = $record['tx_mfaprotect_enable'] = 0;
        }

        // Enforce an access restriction for logged-in users
        if ((bool)($record['tx_mfaprotect_enable'] ?? false) && empty($record['fe_group'])) {
            $fieldArray['fe_group'] = '-2'; // Show at any login
        }
    }
}
