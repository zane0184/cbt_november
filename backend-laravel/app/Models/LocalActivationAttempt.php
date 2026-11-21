<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class LocalActivationAttempt extends Model
{
    protected $fillable = ['installation_identity_id','challenge_id','encrypted_challenge','encrypted_proof_jws','status','challenge_expires_at','completed_at'];
    protected $hidden = ['encrypted_challenge','encrypted_proof_jws'];
    protected $casts = ['challenge_expires_at' => 'immutable_datetime','completed_at' => 'immutable_datetime'];
    public function getChallenge(): string { return Crypt::decryptString($this->encrypted_challenge); }
    public function getProof(): string { return Crypt::decryptString($this->encrypted_proof_jws); }
}
