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

namespace Causal\MfaProtect\Xclass\Container\DataProcessing;

use B13\Container\DataProcessing\ContainerDataProcessingFailedException;
use B13\Container\Domain\Model\Container;
use Causal\MfaProtect\Traits\MfaProtectTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * XCLASS of \B13\Container\DataProcessing\ContainerProcessor to add MFA protection
 * support for content elements inside a container.
 */
class ContainerProcessor extends \B13\Container\DataProcessing\ContainerProcessor
{
    use MfaProtectTrait;

    protected function processColPos(
        ContentObjectRenderer $cObj,
        Container $container,
        int $colPos,
        string $as,
        array $processedData,
        array $processorConfiguration
    ): array {
        $children = $container->getChildrenByColPos($colPos);

        $contentRecordRenderer = $cObj->getContentObject('RECORDS');
        if ($contentRecordRenderer === null) {
            throw new ContainerDataProcessingFailedException('RECORDS content object not available.', 1691483526);
        }
        $conf = [
            'tables' => 'tt_content',
        ];
        foreach ($children as &$child) {
            if (!isset($processorConfiguration['skipRenderingChildContent']) || (int)$processorConfiguration['skipRenderingChildContent'] === 0) {
                $conf['source'] = $child['uid'];
                // XCLASS [start]
                //$child['renderedContent'] = $cObj->render($contentRecordRenderer, $conf);
                $child['renderedContent'] = $this->customRender($cObj, $contentRecordRenderer, $conf);
                // XCLASS [end]
            }
            /** @var ContentObjectRenderer $recordContentObjectRenderer */
            $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $recordContentObjectRenderer->start($child, 'tt_content');
            $child = $this->contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $child);
        }
        $processedData[$as] = $children;
        return $processedData;
    }

    private function customRender(
        ContentObjectRenderer $cObj,
        AbstractContentObject $contentRecordRenderer,
        array $conf
    ): string
    {
        $isMfaProtectEnabled = (bool)GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->select(
                ['tx_mfaprotect_enable'],
                'tt_content',
                ['uid' => $conf['source']]
            )
            ->fetchOne();

        if ($isMfaProtectEnabled) {
            static::$instances++;

            $validTokenProvided = $this->checkNewMfaToken();

            if (!($validTokenProvided || $this->isMfaTokenRecent())) {
                $availableMfaProviders = $this->getAvailableMfaProviders();
                $isFirstOnPage = static::$instances === 1;

                $view = $this->getView();
                $view->assignMultiple([
                    'availableMfaProviders' => $availableMfaProviders,
                    'isFirstOnPage' => $isFirstOnPage,
                ]);

                return $view->render();
            }
        }

        return $cObj->render($contentRecordRenderer, $conf);
    }

    private function getView(): AbstractTemplateView
    {
        $configuration = $this->getConfiguration();

        $context = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();

        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths($configuration['view.']['templateRootPaths.'] ?? []);
        $templatePaths->setLayoutRootPaths($configuration['view.']['layoutRootPaths.'] ?? []);
        $templatePaths->setPartialRootPaths($configuration['view.']['partialRootPaths.'] ?? []);

        $context->setTemplatePaths($templatePaths);
        $context->setControllerName('Content');
        $context->setControllerAction('Cover');

        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class, $context);

        return $view;
    }

    protected function getConfiguration(): array
    {
        $frontendTypoScript = $this->getRequest()->getAttribute('frontend.typoscript');
        return $frontendTypoScript->getSetupArray()['plugin.']['tx_mfaprotect.'] ?? [];
    }

    protected function getSettings(): array
    {
        $configuration = $this->getConfiguration();
        return $configuration['settings.'] ?? [];
    }
}
