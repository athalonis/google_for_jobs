<?php

declare(strict_types=1);

namespace Pegasus\GoogleForJobs\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Migrates job location fields into a dedicated location table.
 */
class MigrateLocationsUpdate implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'migrateLocationsUpdate';
    }

    public function getTitle(): string
    {
        return 'Google For Jobs: migrate locations into seperate table';
    }

    public function getDescription(): string
    {
        return 'This wizard migrates the job location data from the job records to a dedicated table. This is nessecary, because job locations are now separate objects';
    }

    public function executeUpdate(): bool
    {
        return $this->migrateLocationsIntoLocationTable();
    }

    public function updateNecessary(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tx_googleforjobs_domain_model_joblocation'])) {
            return false;
        }

        $locationConnection = $connectionPool->getConnectionForTable('tx_googleforjobs_domain_model_joblocation');
        $locationResult = $locationConnection->createQueryBuilder()
            ->select('*')
            ->from('tx_googleforjobs_domain_model_joblocation')
            ->executeQuery();

        if ($locationResult->fetchAssociative() !== false) {
            return false;
        }

        $jobConnection = $connectionPool->getConnectionForTable('tx_googleforjobs_domain_model_job');
        $jobResult = $jobConnection->createQueryBuilder()
            ->select('*')
            ->from('tx_googleforjobs_domain_model_job')
            ->executeQuery();

        return $jobResult->fetchAssociative() !== false;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    protected function migrateLocationsIntoLocationTable(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionJoblocations = $connectionPool->getConnectionForTable('tx_googleforjobs_domain_model_joblocation');
        $connectionJoblocationsMM = $connectionPool->getConnectionForTable('tx_googleforjobs_domain_model_job_joblocation_mm');

        foreach ($this->getJobData() as $job) {
            $identifiers = [
                'street_address' => $job['job_location_street_address'],
                'city' => $job['job_location_city'],
                'postal_code' => $job['job_location_postal_code'],
                'region' => $job['job_location_region'],
                'country' => $job['job_location_country'],
            ];
            $joblocation = $identifiers;
            $joblocation['pid'] = $job['pid'];

            $locationExists = $connectionJoblocations->select(
                ['uid'],
                'tx_googleforjobs_domain_model_joblocation',
                $identifiers
            )->fetchAllAssociative();

            if ($locationExists === []) {
                $connectionJoblocations->insert('tx_googleforjobs_domain_model_joblocation', $joblocation);
            }

            $locationUid = $connectionJoblocations->select(
                ['uid'],
                'tx_googleforjobs_domain_model_joblocation',
                $identifiers
            )->fetchAssociative();

            if ($locationUid === false) {
                continue;
            }

            $connectionJoblocationsMM->insert(
                'tx_googleforjobs_domain_model_job_joblocation_mm',
                [
                    'uid_local' => $locationUid['uid'],
                    'uid_foreign' => $job['uid'],
                    'sorting_foreign' => 1,
                ]
            );
        }

        return true;
    }

    protected function getJobData(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionJobs = $connectionPool->getConnectionForTable('tx_googleforjobs_domain_model_job');

        $queryBuilder = $connectionJobs->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder->select('*')
            ->from('tx_googleforjobs_domain_model_job')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
