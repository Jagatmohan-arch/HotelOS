<?php
/**
 * HotelOS - Chain Context Manager
 * 
 * MODULE 1: MULTI-PROPERTY MANAGEMENT
 * Manages the current chain context for enterprise users.
 */

declare(strict_types=1);

namespace HotelOS\Core;

class ChainContext
{
    private static ?int $chainId = null;
    private static ?array $chain = null;

    /**
     * Set the current chain context
     */
    public static function set(int|string $chainId, ?array $chainData = null): void
    {
        self::$chainId = (int) $chainId;
        self::$chain = $chainData;
    }

    /**
     * Get the current chain ID
     */
    public static function getId(): ?int
    {
        return self::$chainId;
    }

    /**
     * Check if chain context is active
     */
    public static function isActive(): bool
    {
        return self::$chainId !== null;
    }

    /**
     * Clear chain context
     */
    public static function clear(): void
    {
        self::$chainId = null;
        self::$chain = null;
    }
}
