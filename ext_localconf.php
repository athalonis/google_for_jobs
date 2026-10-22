<?php

defined('TYPO3') || die('Access denied.');

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'GoogleForJobs',
        'Job',
        [
            \Pegasus\GoogleForJobs\Controller\JobController::class => 'list, show, listSelected, noJobFound',
        ],
        [
            \Pegasus\GoogleForJobs\Controller\JobController::class => '',
        ],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    job {
                        iconIdentifier = google_for_jobs-plugin-job
                        title = LLL:EXT:google_for_jobs/Resources/Private/Language/locallang_db.xlf:tx_google_for_jobs_job.name
                        description = LLL:EXT:google_for_jobs/Resources/Private/Language/locallang_db.xlf:tx_google_for_jobs_job.description
                        tt_content_defValues {
                            CType = list
                            list_type = googleforjobs_job
                        }
                    }
                }
                show = *
            }
       }'
    );
});

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['migrateLocationsUpdate']
    = \Pegasus\GoogleForJobs\Updates\MigrateLocationsUpdate::class;
