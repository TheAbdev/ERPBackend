<?php

namespace App\Core\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'type',
    ];

    public function leads(): MorphToMany
    {
        return $this->morphedByMany(\App\Modules\CRM\Models\Lead::class, 'taggable');
    }

    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\App\Modules\CRM\Models\Contact::class, 'taggable');
    }

    public function accounts(): MorphToMany
    {
        return $this->morphedByMany(\App\Modules\CRM\Models\Account::class, 'taggable');
    }

    public function deals(): MorphToMany
    {
        return $this->morphedByMany(\App\Modules\CRM\Models\Deal::class, 'taggable');
    }
}

