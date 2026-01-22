<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\ContentBlock;

class ContentBlockPolicy
{
    /**
     * Determine if the user can view any content blocks.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.content_blocks.view');
    }

    /**
     * Determine if the user can view the content block.
     */
    public function view(User $user, ContentBlock $contentBlock): bool
    {
        return $user->hasPermission('ecommerce.content_blocks.view') 
            && $user->tenant_id === $contentBlock->tenant_id;
    }

    /**
     * Determine if the user can create content blocks.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ecommerce.content_blocks.create');
    }

    /**
     * Determine if the user can update the content block.
     */
    public function update(User $user, ContentBlock $contentBlock): bool
    {
        return $user->hasPermission('ecommerce.content_blocks.update') 
            && $user->tenant_id === $contentBlock->tenant_id;
    }

    /**
     * Determine if the user can delete the content block.
     */
    public function delete(User $user, ContentBlock $contentBlock): bool
    {
        return $user->hasPermission('ecommerce.content_blocks.delete') 
            && $user->tenant_id === $contentBlock->tenant_id;
    }
}







