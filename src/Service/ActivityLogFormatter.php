<?php

namespace App\Service;

use App\Entity\ActivityLog;

class ActivityLogFormatter
{
    /**
     * Format activity log entries into a human-readable format
     */
    public function format(ActivityLog $activityLog): array
    {
        $targetData = $activityLog->getTargetData();
        $decodedTargetData = null;
        
        if ($targetData) {
            $decodedTargetData = json_decode($targetData, true);
            if ($decodedTargetData === null && json_last_error() !== JSON_ERROR_NONE) {
                // If JSON decode fails, keep as string
                $decodedTargetData = $targetData;
            }
        }
        
        // Get the created date and ensure it's in the correct timezone
        $createdAt = $activityLog->getCreatedAt();
        $formattedDate = null;
        if ($createdAt) {
            // Convert to Asia/Manila timezone for display
            $tz = new \DateTimeZone('Asia/Manila');
            $createdAt->setTimezone($tz);
            $formattedDate = $createdAt->format('M d, Y - h:i A');
        }
        
        return [
            'id' => $activityLog->getId(),
            'username' => $activityLog->getUsername(),
            'userId' => $activityLog->getUserId()?->getId(),
            'role' => $activityLog->getRole(),
            'action' => $activityLog->getAction(),
            'actionLabel' => $this->getActionLabel($activityLog->getAction()),
            'targetData' => $decodedTargetData,
            'ipAddress' => $activityLog->getIpAddress(),
            'createdAt' => $createdAt,
            'createdAtFormatted' => $formattedDate,
        ];
    }

    /**
     * Get human-readable label for an action
     */
    private function getActionLabel(string $action): string
    {
        $labels = [
            'USER_LOGIN' => 'User Login',
            'USER_LOGOUT' => 'User Logout',
            'USER_CREATED' => 'User Created',
            'USER_UPDATED' => 'User Updated',
            'USER_DELETED' => 'User Deleted',
            'USER_STATUS_CHANGED' => 'User Status Changed',
            'PASSWORD_CHANGED' => 'Password Changed',
            'ORDER_CREATED' => 'Order Created',
            'ORDER_UPDATED' => 'Order Updated',
            'ORDER_DELETED' => 'Order Deleted',
            'PRODUCT_CREATED' => 'Product Created',
            'PRODUCT_UPDATED' => 'Product Updated',
            'PRODUCT_DELETED' => 'Product Deleted',
            'INVENTORY_CREATED' => 'Inventory Created',
            'INVENTORY_UPDATED' => 'Inventory Updated',
            'INVENTORY_DELETED' => 'Inventory Deleted',
            'CATEGORY_CREATED' => 'Category Created',
            'CATEGORY_UPDATED' => 'Category Updated',
            'CATEGORY_DELETED' => 'Category Deleted',
        ];

        return $labels[$action] ?? $action;
    }

    /**
     * Get CSS class for action badge
     */
    public function getActionClass(string $action): string
    {
        return match(true) {
            str_contains($action, 'LOGIN') || str_contains($action, 'LOGOUT') => 'badge-info',
            str_contains($action, 'CREATED') => 'badge-success',
            str_contains($action, 'UPDATED') => 'badge-warning',
            str_contains($action, 'DELETED') => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
