<?php
namespace Fab\Vidi\Tool;

/*
 * This file is part of the Fab/Vidi project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class RelationAnalyserTool
 */
class RelationAnalyserTool extends AbstractTool
{

    /**
     * Display the title of the tool on the welcome screen.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return LocalizationUtility::translate(
            'analyse_relations',
            'vidi'
        );
    }

    /**
     * Display the description of the tool in the welcome screen.
     *
     * @return string
     */
    public function getDescription(): string
    {
        $templateNameAndPath = 'EXT:vidi/Resources/Private/Standalone/Tool/RelationAnalyser/Launcher.html';
        $view = $this->initializeStandaloneView($templateNameAndPath);
        $view->assign('sitePath', \TYPO3\CMS\Core\Core\Environment::getPublicPath() );
        $view->assign('dataType', $this->getModuleLoader()->getDataType());
        return $view->render();
    }

    /**
     * Do the job
     *
     * @param array $arguments
     * @return string
     */
    public function work(array $arguments = array()): string
    {

        $templateNameAndPath = 'EXT:vidi/Resources/Private/Standalone/Tool/RelationAnalyser/WorkResult.html';
        $view = $this->initializeStandaloneView($templateNameAndPath);

        $dataType = $this->getModuleLoader()->getDataType();
        $analyse = $this->getGridAnalyserService()->checkRelationForTable($dataType);

        if (empty($analyse)) {
            $result = 'No relation involved in this Grid.';
        } else {
            $result = implode("\n", $analyse);
        }

        $view->assign('result', $result);
        $view->assign('dataType', $dataType);

        return $view->render();
    }

    /**
     * Tell whether the tools should be displayed according to the context.
     *
     * @return bool
     */
    public function isShown(): bool
    {
        return $this->getBackendUser()->isAdmin();# && GeneralUtility::getApplicationContext()->isDevelopment();
    }

    /**
     * Get the Vidi Module Loader.
     *
     * @return \Fab\Vidi\Module\ModuleLoader|object
     */
    protected function getModuleLoader(): \Fab\Vidi\Module\ModuleLoader
    {
        return GeneralUtility::makeInstance(\Fab\Vidi\Module\ModuleLoader::class);
    }

    /**
     * Get the Vidi Module Loader.
     *
     * @return \Fab\Vidi\Grid\GridAnalyserService|object
     */
    protected function getGridAnalyserService()
    {
        return GeneralUtility::makeInstance(\Fab\Vidi\Grid\GridAnalyserService::class);
    }
}

