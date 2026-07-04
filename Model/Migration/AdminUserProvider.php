<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

use Magento\Framework\App\ResourceConnection;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;

/**
 * Provides admin-user facts (ids, counts, role names) used by the migration
 * dashboard and the security score engine. Reads real Magento admin users.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class AdminUserProvider
{
    public function __construct(
        private readonly UserCollectionFactory $userCollectionFactory,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Get admin-user ids.
     *
     * @param bool $activeOnly When true, only active admin accounts are returned.
     * @return int[]
     */
    public function getAdminIds(bool $activeOnly = false): array
    {
        $collection = $this->userCollectionFactory->create();
        if ($activeOnly) {
            $collection->addFieldToFilter('is_active', ['eq' => 1]);
        }

        $ids = [];
        foreach ($collection->getAllIds() as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * Count admin users.
     *
     * @param bool $activeOnly When true, only active admin accounts are counted.
     * @return int
     */
    public function countAdmins(bool $activeOnly = false): int
    {
        $collection = $this->userCollectionFactory->create();
        if ($activeOnly) {
            $collection->addFieldToFilter('is_active', ['eq' => 1]);
        }

        return (int) $collection->getSize();
    }

    /**
     * Build a map of admin-user id => role (group) name.
     *
     * @return array<int, string>
     */
    public function getRoleNameMap(): array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $roleTable = $this->resourceConnection->getTableName('authorization_role');

            $select = $connection->select()
                ->from(['ur' => $roleTable], ['user_id' => 'ur.user_id'])
                ->joinInner(
                    ['ar' => $roleTable],
                    'ar.role_id = ur.parent_id',
                    ['role_name' => 'ar.role_name']
                )
                ->where('ur.role_type = ?', 'U')
                ->where('ur.user_id > 0');

            $map = [];
            foreach ($connection->fetchAll($select) as $row) {
                $map[(int) $row['user_id']] = (string) $row['role_name'];
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }
}
