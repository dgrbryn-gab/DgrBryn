<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogFormatter
{
    private ?RequestStack $requestStack;

    public function __construct(?RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
    }
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
        
      
        $createdAt = $activityLog->getCreatedAt();
        $formattedDate = null;
        if ($createdAt) {
            $createdAtDisplay = clone $createdAt;
            $userTimezone = $this->getUserTimezone();
            try {
                $createdAtDisplay->setTimezone(new \DateTimeZone($userTimezone));
            } catch (\Exception $e) {
                // Fallback to UTC if timezone is invalid
                error_log('Invalid timezone: ' . $userTimezone . ', error: ' . $e->getMessage());
                $createdAtDisplay->setTimezone(new \DateTimeZone('UTC'));
            }
            $formattedDate = $createdAtDisplay->format('M d, Y - h:i A');
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

    /**
     * Get the user's timezone from browser or fallback to UTC
     */
    private function getUserTimezone(): string
    {
        $timezone = 'UTC'; // default
        
        try {
            // Try to get from session first
            if ($this->requestStack) {
                $request = $this->requestStack->getCurrentRequest();
                if ($request) {
                    try {
                        $session = $request->getSession();
                        if ($session && $session->has('user_timezone')) {
                            $tz = $session->get('user_timezone');
                            if ($this->isValidTimezone($tz)) {
                                $timezone = $tz;
                                error_log('Using timezone from session: ' . $timezone);
                                return $timezone;
                            }
                        }
                    } catch (\Exception $e) {
                        // Session not available
                    }

                    // Try to get from cookie
                    if ($request->cookies->has('user_timezone')) {
                        $tz = $request->cookies->get('user_timezone');
                        // Decode if URL encoded
                        $tz = urldecode($tz);
                        if ($this->isValidTimezone($tz)) {
                            $timezone = $tz;
                            error_log('Using timezone from cookie: ' . $timezone);
                            return $timezone;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Error getting user timezone: ' . $e->getMessage());
        }

        error_log('Using default timezone: ' . $timezone);
        return $timezone;
    }

    /**
     * Validate if a timezone string is valid
     */
    private function isValidTimezone(string $timezone): bool
    {
        try {
            new \DateTimeZone($timezone);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
