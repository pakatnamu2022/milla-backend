<?php

namespace App\Traits;

trait HasReceptionBehavior
{
  /**
   * Calculate and update totals for reception
   *
   * @return void
   */
  public function calculateTotals(): void
  {
    $this->total_items = $this->details()->count();
    $this->total_quantity = $this->details()->sum('quantity_received');
    $this->save();
  }

  /**
   * Validate that quantities are consistent
   *
   * @return bool
   */
  public function validateQuantities(): bool
  {
    foreach ($this->details as $detail) {
      // quantity_received cannot be negative
      if ($detail->quantity_received < 0) {
        return false;
      }

      // observed_quantity cannot be negative
      if ($detail->observed_quantity < 0) {
        return false;
      }

      // observed_quantity cannot exceed quantity_sent
      if ($detail->observed_quantity > $detail->quantity_sent) {
        return false;
      }
    }

    return true;
  }

  /**
   * Check if reception has observations
   *
   * @return bool
   */
  public function hasObservations(): bool
  {
    return $this->details()->where('observed_quantity', '>', 0)->exists();
  }

  /**
   * Get total observed quantity
   *
   * @return float
   */
  public function getTotalObservedQuantity(): float
  {
    return $this->details()->sum('observed_quantity');
  }

  /**
   * Check if reception is fully completed (no pending items)
   *
   * @return bool
   */
  public function isFullyReceived(): bool
  {
    foreach ($this->details as $detail) {
      if ($detail->quantity_received < $detail->quantity_sent) {
        return false;
      }
    }

    return true;
  }

  /**
   * Get percentage of completion
   *
   * @return float
   */
  public function getCompletionPercentage(): float
  {
    $totalSent = $this->details()->sum('quantity_sent');
    $totalReceived = $this->details()->sum('quantity_received');

    if ($totalSent == 0) {
      return 0;
    }

    return round(($totalReceived / $totalSent) * 100, 2);
  }
}