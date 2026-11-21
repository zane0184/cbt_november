<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalInstallationIdentity extends Model
{
    protected $fillable = ['tenant_id','installation_id','key_id','public_key','encrypted_private_key','key_fingerprint','registered_domain','product_code','product_version'];
    protected $hidden = ['encrypted_private_key'];
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function licenseState() { return $this->hasOne(LocalLicenseState::class, 'installation_identity_id'); }
}
