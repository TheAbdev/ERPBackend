<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base model for all ERP models.
 * Provides tenant scoping and change tracking.
 */
abstract class ErpBaseModel extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, ModelChangeTracker;

    /**
     * Get the module name for this model.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return 'erp';
    }
}

