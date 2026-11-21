<?php

namespace App\Livewire\Proktor;

use Livewire\Component;

/**
 * Retired legacy portal component.
 *
 * Proktor is no longer a permanent role or portal. Temporary supervision is
 * assigned to an eligible Guru and managed from the Admin Madrasah surface.
 * This tombstone deliberately returns 404 if an old component snapshot tries
 * to mount it.
 */
class ProktorDashboard extends Component
{
    public function mount(): void
    {
        abort(404);
    }

    public function render()
    {
        abort(404);
    }
}
