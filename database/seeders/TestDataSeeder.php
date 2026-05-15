<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Material;
use App\Models\MaterialPurchase;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    private const TOTAL_REPORTS = 250;

    private const REPAIRED_REPORTS = 188;

    private const VERIFIED_REPORTS = 37;

    private const SCHEDULED_REPORTS = 25;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'staging'])) {
            throw new \RuntimeException('TestDataSeeder may only run in local, testing, or staging environments.');
        }

        DB::transaction(function (): void {
            $this->truncateTestData();

            $users = $this->createStaffUsers();
            $vendors = $this->seedVendors();
            $materials = $this->seedAsphaltBagMaterials($vendors, (int) $users['accountant']->id);
            $potholeCategory = $this->seedPotholeCategory();

            $reports = $this->seedReports($potholeCategory->id);
            $jobs = $this->seedJobsForReports($reports, (int) $users['manager']->id, (int) $users['service_worker']->id);
            $this->seedAsphaltExpenses($jobs, $materials, $vendors, (int) $users['accountant']->id);

            $this->command?->info('TestDataSeeder completed.');
            $this->command?->info('Reports: '.$reports->count().' ('.self::REPAIRED_REPORTS.' repaired, '.self::VERIFIED_REPORTS.' verified, '.self::SCHEDULED_REPORTS.' scheduled)');
            $this->command?->info('Jobs: '.$jobs->count());
            $this->command?->info('Expenses: '.$jobs->count().' (three asphalt bag sizes)');
        });
    }

    private function seedPotholeCategory(): ReportCategory
    {
        ReportCategory::query()->delete();

        return ReportCategory::create([
            'slug' => 'pothole',
            'label_en' => 'Pothole',
            'label_fr' => 'Nid-de-poule',
            'icon' => 'circle-dot',
            'color' => '#EF4444',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * @return Collection<int, Vendor>
     */
    private function seedVendors(): Collection
    {
        Vendor::query()->delete();

        $vendorRows = [
            [
                'name' => 'Montreal Asphalt Supply',
                'contact_name' => 'Luc Bergeron',
                'email' => 'sales@mtlasphalt.ca',
                'phone' => '514-555-0101',
                'address' => '1200 Rue Wellington, Montreal, QC',
                'website' => 'https://mtlasphalt.ca',
            ],
            [
                'name' => 'Nordic Road Materials',
                'contact_name' => 'Emma Cote',
                'email' => 'orders@nordicroad.ca',
                'phone' => '514-555-0102',
                'address' => '875 Boulevard Industriel, Montreal, QC',
                'website' => 'https://nordicroad.ca',
            ],
            [
                'name' => 'Quebec Paving Depot',
                'contact_name' => 'Marc Gagnon',
                'email' => 'info@qcpaving.ca',
                'phone' => '514-555-0103',
                'address' => '4100 Rue Notre-Dame O, Montreal, QC',
                'website' => 'https://qcpaving.ca',
            ],
        ];

        $vendors = collect();

        foreach ($vendorRows as $row) {
            $vendors->push(
                Vendor::updateOrCreate(
                    ['name' => $row['name']],
                    [
                        'contact_name' => $row['contact_name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'address' => $row['address'],
                        'website' => $row['website'],
                        'is_active' => true,
                    ]
                )
            );
        }

        return $vendors;
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @return Collection<int, Material>
     */
    private function seedAsphaltBagMaterials(Collection $vendors, int $accountantId): Collection
    {
        Material::query()->delete();

        $materialRows = [
            [
                'vendor' => 'Montreal Asphalt Supply',
                'sku' => 'ASP-SMALL-15KG',
                'name' => 'Small asphalt repair bag',
                'description' => '15 kg cold-mix asphalt bag for small pothole repairs.',
                'current_stock' => 850,
                'min_stock_alert' => 100,
                'unit_cost' => 14.75,
                'quantity' => 850,
            ],
            [
                'vendor' => 'Nordic Road Materials',
                'sku' => 'ASP-MEDIUM-25KG',
                'name' => 'Medium asphalt repair bag',
                'description' => '25 kg cold-mix asphalt bag for standard pothole repairs.',
                'current_stock' => 650,
                'min_stock_alert' => 80,
                'unit_cost' => 22.50,
                'quantity' => 650,
            ],
            [
                'vendor' => 'Quebec Paving Depot',
                'sku' => 'ASP-LARGE-40KG',
                'name' => 'Large asphalt repair bag',
                'description' => '40 kg cold-mix asphalt bag for large pothole repairs.',
                'current_stock' => 425,
                'min_stock_alert' => 60,
                'unit_cost' => 34.25,
                'quantity' => 425,
            ],
        ];

        $materials = collect();

        foreach ($materialRows as $row) {
            $vendor = $vendors->firstWhere('name', $row['vendor']);
            if (! $vendor) {
                throw new \RuntimeException("Missing vendor '{$row['vendor']}' for material seed.");
            }

            $material = Material::create([
                'sku' => $row['sku'],
                'name' => $row['name'],
                'description' => $row['description'],
                'unit' => 'bag',
                'current_stock' => $row['current_stock'],
                'reserved_stock' => 0,
                'min_stock_alert' => $row['min_stock_alert'],
                'avg_purchase_price' => $row['unit_cost'],
                'last_purchase_price' => $row['unit_cost'],
                'location' => 'Main warehouse',
                'is_active' => true,
            ]);

            $this->createMaterialPurchase($material, $vendor, (float) $row['quantity'], (float) $row['unit_cost'], $accountantId);
            $materials->push($material);
        }

        return $materials;
    }

    private function truncateTestData(): void
    {
        DB::statement('TRUNCATE TABLE job_materials, job_workers, job_reports, material_purchases, expenses, repair_jobs, reports RESTART IDENTITY CASCADE');
    }

    /**
     * @return array<string, User>
     */
    private function createStaffUsers(): array
    {
        $roles = Role::query()->get()->keyBy('slug');

        $staff = [
            'manager' => ['name' => 'Marie Gestionnaire', 'email' => 'manager@nidvite.test'],
            'service_worker' => ['name' => 'Jean Travailleur', 'email' => 'worker@nidvite.test'],
            'accountant' => ['name' => 'Pierre Comptable', 'email' => 'accountant@nidvite.test'],
            'viewer' => ['name' => 'Sophie Lectrice', 'email' => 'viewer@nidvite.test'],
        ];

        $users = [];

        foreach ($staff as $slug => $data) {
            $role = $roles->get($slug);
            if (! $role) {
                throw new \RuntimeException("Missing role with slug '{$slug}'. Run RoleSeeder first.");
            }

            $users[$slug] = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'role_id' => $role->id,
                    'locale' => 'fr',
                    'is_active' => true,
                ]
            );
        }

        return $users;
    }

    /**
     * @return Collection<int, Report>
     */
    private function seedReports(int $potholeCategoryId): Collection
    {
        $this->assertMontrealRoadsSeeded();

        $distribution = [
            'repaired' => self::REPAIRED_REPORTS,
            'verified' => self::VERIFIED_REPORTS,
            'scheduled' => self::SCHEDULED_REPORTS,
        ];

        $reports = collect();

        foreach ($distribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $createdAt = match ($status) {
                    'repaired' => now()->subDays(rand(7, 29))->subMinutes(rand(0, 1439)),
                    'scheduled' => now()->subDays(rand(0, 7))->subMinutes(rand(0, 1439)),
                    default => now()->subDays(rand(0, 14))->subMinutes(rand(0, 1439)),
                };
                $location = $this->randomMontrealLocation();
                $nearestRoad = $this->getNearestRoadForLocation($location);
                $streetName = $nearestRoad['street_name'];
                $borough = trim($nearestRoad['borough']);

                if ($borough === '' || in_array(mb_strtolower($borough), ['montreal', 'n/a'], true)) {
                    $borough = null;
                }

                $reportId = DB::table('reports')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'public_tracking_id' => $this->generatePublicTrackingId(),
                    'reporter_email' => 'citizen'.rand(1000, 999999).'@example.com',
                    'preferred_locale' => rand(0, 1) ? 'fr' : 'en',
                    'status' => $status,
                    'priority' => 'normal',
                    'category_id' => $potholeCategoryId,
                    'description' => 'Pothole reported on road surface.',
                    'address' => rand(100, 9999).' '.$streetName,
                    'neighborhood' => $borough,
                    'borough' => $borough,
                    'geofence_passed' => true,
                    'is_spam' => false,
                    'first_scheduled_at' => in_array($status, ['scheduled', 'repaired'], true)
                        ? $createdAt->copy()->addHours(rand(6, 72))
                        : null,
                    'first_started_at' => $status === 'repaired'
                        ? $createdAt->copy()->addHours(rand(12, 96))
                        : null,
                    'completed_at' => $status === 'repaired'
                        ? $createdAt->copy()->addHours(rand(24, 168))
                        : null,
                    'rejection_reason' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                DB::statement(
                    'UPDATE reports SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
                    [$location[1], $location[0], $reportId]
                );

                $report = Report::query()->findOrFail($reportId);
                $reports->push($report);
            }
        }

        return $reports;
    }

    private function generatePublicTrackingId(): string
    {
        do {
            $candidate = 'MTL'.strtoupper(Str::random(8));
        } while (Report::query()->where('public_tracking_id', $candidate)->exists());

        return $candidate;
    }

    /**
     * Generate a realistic location on an actual Montreal street.
     *
     * @return array{float, float} [lat, lng]
     */
    private function randomMontrealLocation(): array
    {
        $result = DB::selectOne(
            'SELECT 
                ST_Y(point) as lat, 
                ST_X(point) as lng
            FROM (
                SELECT ST_LineInterpolatePoint(geom, random()) as point
                FROM montreal_roads
                ORDER BY RANDOM()
                LIMIT 1
            ) as t'
        );

        if ($result) {
            return [round($result->lat, 6), round($result->lng, 6)];
        }

        throw new \RuntimeException('Unable to generate demo report location because no Montreal road geometry was selected.');
    }

    private function assertMontrealRoadsSeeded(): void
    {
        if (DB::table('montreal_roads')->where('source', 'mtl_geobase')->doesntExist()) {
            throw new \RuntimeException('Montreal roads must be seeded from database/geo/mtl_geobase.json before TestDataSeeder runs.');
        }
    }

    /**
     * Get nearest road metadata for a location.
     *
     * @param  array{float, float}  $location  [lat, lng]
     * @return array{street_name: string, borough: string}
     */
    private function getNearestRoadForLocation(array $location): array
    {
        $result = DB::selectOne(
            'SELECT name, borough FROM montreal_roads
             WHERE ST_DWithin(
                geom::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                50
             )
             ORDER BY ST_Distance(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)
             LIMIT 1',
            [$location[1], $location[0], $location[1], $location[0]]
        );

        if (! $result) {
            return collect([
                ['street_name' => 'Rue Sainte-Catherine', 'borough' => 'Ville-Marie'],
                ['street_name' => 'Avenue du Parc', 'borough' => 'Le Plateau-Mont-Royal'],
                ['street_name' => 'Boulevard Saint-Laurent', 'borough' => 'Rosemont-La Petite-Patrie'],
                ['street_name' => 'Rue Jean-Talon', 'borough' => 'Villeray-Saint-Michel-Parc-Extension'],
                ['street_name' => 'Chemin Queen Mary', 'borough' => 'Cote-des-Neiges-Notre-Dame-de-Grace'],
            ])->random();
        }

        return [
            'street_name' => $result->name ? trim($result->name) : 'Rue Sainte-Catherine',
            'borough' => $result->borough ? trim($result->borough) : 'Ville-Marie',
        ];
    }

    /**
     * @param  Collection<int, Report>  $reports
     * @return Collection<int, RepairJob>
     */
    private function seedJobsForReports(Collection $reports, int $managerId, int $workerId): Collection
    {
        $jobs = collect();

        foreach ($reports as $report) {
            if ($report->status === 'verified') {
                continue;
            }

            $jobStatus = match ($report->status) {
                'repaired' => 'completed',
                'scheduled' => 'planned',
                default => throw new \RuntimeException("Unexpected report status '{$report->status}' for job seed."),
            };

            $scheduledAt = $report->first_scheduled_at ?? $report->created_at->copy()->addDays(rand(1, 5));
            $startedAt = $jobStatus === 'completed'
                ? ($report->first_started_at ?? $scheduledAt->copy()->addHours(rand(4, 24)))
                : null;
            $completedAt = $jobStatus === 'completed'
                ? ($report->completed_at ?? $startedAt?->copy()->addHours(rand(6, 48)))
                : null;

            $job = RepairJob::create([
                'title' => $this->repairJobTitle($report, $jobStatus),
                'description' => 'Pothole repair generated from public report '.$report->public_tracking_id.'.',
                'scheduled_at' => $scheduledAt,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'status' => $jobStatus,
                'created_by' => $managerId,
                'estimated_cost' => 150.00,
                'actual_cost' => $jobStatus === 'completed' ? 150.00 : null,
            ]);

            $job->reports()->attach($report->id, ['cost_allocation_percentage' => 100]);
            $job->users()->attach($workerId, ['role_in_job' => 'lead', 'hours_worked' => $jobStatus === 'completed' ? 3 : 1]);

            $jobs->push($job);
        }

        return $jobs;
    }

    private function repairJobTitle(Report $report, string $jobStatus): string
    {
        $statusLabel = $jobStatus === 'completed' ? 'Completed' : 'Scheduled';
        $area = $report->borough ?: $report->neighborhood ?: 'Montreal';
        $street = $report->address ? preg_replace('/^\d+\s+/', '', $report->address) : 'unpinned street';
        $date = ($jobStatus === 'completed' ? $report->completed_at : $report->first_scheduled_at)?->format('M j');

        return trim("{$statusLabel} pothole repair - {$area} - {$street}".($date ? " ({$date})" : ''));
    }

    /**
     * @param  Collection<int, RepairJob>  $jobs
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Vendor>  $vendors
     */
    private function seedAsphaltExpenses(Collection $jobs, Collection $materials, Collection $vendors, int $accountantId): void
    {
        foreach ($jobs as $job) {
            $material = $materials->random();
            $vendor = $this->vendorForMaterial($material, $vendors);
            $quantity = $job->status === 'completed' ? rand(1, 6) : rand(1, 3);
            $unitCost = (float) $material->last_purchase_price;
            $subtotal = $quantity * $unitCost;
            $taxRate = 0.14975;
            $taxAmount = round($subtotal * $taxRate, 2);
            $total = round($subtotal + $taxAmount, 2);

            Expense::create([
                'repair_job_id' => $job->id,
                'material_id' => $material->id,
                'description' => $material->name,
                'quantity' => $quantity,
                'unit' => 'bag',
                'unit_cost' => $unitCost,
                'subtotal' => round($subtotal, 2),
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'vendor_id' => $vendor->id,
                'vendor' => $vendor->name,
                'incurred_at' => $job->completed_at ?? $job->scheduled_at ?? now(),
                'created_by' => $accountantId,
            ]);

            DB::table('job_materials')->updateOrInsert(
                [
                    'repair_job_id' => $job->id,
                    'material_id' => $material->id,
                ],
                [
                    'quantity_planned' => $quantity,
                    'quantity_actual' => $job->status === 'completed' ? $quantity : null,
                    'unit_cost_at_time' => $unitCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     */
    private function vendorForMaterial(Material $material, Collection $vendors): Vendor
    {
        $vendorName = match ($material->sku) {
            'ASP-SMALL-15KG' => 'Montreal Asphalt Supply',
            'ASP-MEDIUM-25KG' => 'Nordic Road Materials',
            'ASP-LARGE-40KG' => 'Quebec Paving Depot',
            default => null,
        };

        $vendor = $vendorName ? $vendors->firstWhere('name', $vendorName) : null;

        if (! $vendor) {
            throw new \RuntimeException("Missing vendor for asphalt material '{$material->sku}'.");
        }

        return $vendor;
    }

    private function createMaterialPurchase(Material $material, Vendor $vendor, float $quantity, float $unitCost, int $accountantId): void
    {
        $subtotal = $quantity * $unitCost;
        $taxRate = 0.14975;
        $taxAmount = round($subtotal * $taxRate, 2);

        MaterialPurchase::create([
            'material_id' => $material->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
            'vendor' => $vendor->name,
            'stock_updated' => true,
            'purchased_at' => now()->subDays(35),
            'created_by' => $accountantId,
        ]);
    }
}
