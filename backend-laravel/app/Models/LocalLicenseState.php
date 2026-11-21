<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalLicenseState extends Model
{
    protected $fillable = ['installation_identity_id','license_id','status','license_revision','lease_revision','policy_version','trusted_key_id','trusted_public_key','signed_license_artifact','signed_lease_artifact','license_expires_at','lease_expires_at','last_trusted_server_time','last_observed_at','last_publisher_check_at','last_error_code'];
    protected $hidden = ['trusted_public_key','signed_license_artifact','signed_lease_artifact'];
    protected $casts = ['license_revision' => 'integer','lease_revision' => 'integer','policy_version' => 'integer','license_expires_at' => 'immutable_datetime','lease_expires_at' => 'immutable_datetime','last_trusted_server_time' => 'immutable_datetime','last_observed_at' => 'immutable_datetime','last_publisher_check_at' => 'immutable_datetime'];
    public function installation() { return $this->belongsTo(LocalInstallationIdentity::class, 'installation_identity_id'); }
}
