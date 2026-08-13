<?php

namespace App\POS\Exceptions;

use RuntimeException;

/**
 * Domain rule violation in the inventory ledger
 * (negative stock, cross-store posting, immutability, duplicate source, ...).
 */
class InventoryException extends RuntimeException
{
}
