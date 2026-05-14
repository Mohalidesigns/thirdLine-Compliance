<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Multi-tenancy scope trait.
 *
 * MVP implementation: every model carrying this trait is scoped to a single
 * tenant. In the MVP there is only one tenant (id = 1) and the resolver is a
 * stub. The public interface — getTenantId(), newQuery() with a global scope,
 * and the boot hook that stamps tenant_id on create — is intentionally
 * identical to what a real multi-tenant implementation will require, so
 * call-site code does not change when we swap the stub resolver for a
 * proper Tenant service in a later phase.
 *
 * [NEEDS DECISION] Replace static stub with a TenantResolver service that
 * reads the tenant from the authenticated user before going live with
 * multiple tenants.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait and register a global scope that filters all queries
     * to the current tenant, plus a creating listener that stamps tenant_id.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $query->where(
                $query->getModel()->getTable().'.tenant_id',
                static::currentTenantId(),
            );
        });

        static::creating(function (Model $model): void {
            if (empty($model->tenant_id)) {
                $model->tenant_id = static::currentTenantId();
            }
        });
    }

    /**
     * Resolve the current tenant ID.
     *
     * MVP: returns 1 (single-tenant stub).
     * Replace with a proper TenantResolver before multi-tenant go-live.
     */
    public static function currentTenantId(): int
    {
        return (int) (app()->bound('current.tenant_id')
            ? app('current.tenant_id')
            : 1);
    }

    /**
     * Initialise the tenant_id attribute so it is always cast to int.
     */
    public function initializeBelongsToTenant(): void
    {
        $this->casts['tenant_id'] = 'integer';
    }
}
