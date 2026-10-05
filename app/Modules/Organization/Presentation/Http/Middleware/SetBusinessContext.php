<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use StoreYar\Modules\Organization\Domain\Contracts\BranchRepository;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\ValueObjects\BranchId;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;
use Symfony\Component\HttpFoundation\Response;

final class SetBusinessContext
{
    public function __construct(
        private CurrentBusinessContext $context,
        private OrganizationRepository $organizations,
        private BranchRepository $branches,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->header('X-Business-Id');

        if ($businessId === null || $businessId === '') {
            return response()->json([
                'message' => 'X-Business-Id header is required.',
            ], 400);
        }

        try {
            $organizationId = OrganizationId::fromString($businessId);
        } catch (\InvalidArgumentException) {
            return response()->json([
                'message' => 'Invalid or inactive business.',
            ], 403);
        }

        $organization = $this->organizations->findById($organizationId);

        if ($organization === null || ! $organization->status()->isActive()) {
            return response()->json([
                'message' => 'Invalid or inactive business.',
            ], 403);
        }

        $branchHeader = $request->header('X-Branch-Id');

        if ($branchHeader !== null && $branchHeader !== '') {
            try {
                $branchId = BranchId::fromString($branchHeader);
            } catch (\InvalidArgumentException) {
                return response()->json([
                    'message' => 'Invalid or inactive branch.',
                ], 403);
            }

            $branch = $this->branches->findById($branchId);

            if (
                $branch === null
                || ! $branch->organizationId()->equals($organization->organizationId())
                || ! $branch->status()->isActive()
            ) {
                return response()->json([
                    'message' => 'Invalid or inactive branch.',
                ], 403);
            }

            $this->context->setBusiness(
                $organization->organizationId()->value(),
                $branch->branchId()->value(),
            );
        } else {
            $this->context->setBusiness(
                $organization->organizationId()->value(),
            );
        }

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
