<?php

namespace App\Modules\Website\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base model for Website module.
 */
abstract class WebsiteBaseModel extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, ModelChangeTracker;

    /**
     * Get the module name for this model.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return 'website';
    }
}
