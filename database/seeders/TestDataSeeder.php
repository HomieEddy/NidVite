<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Material;
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
    private const TOTAL_REPORTS = 200;

    private const REJECTED_REPORTS = 10;

    /** @var array<array{float, float}> */
    private array $montrealCenters = [
        [45.5017, -73.5673], // Ville-Marie
        [45.5242, -73.5810], // Plateau
        [45.5510, -73.6100], // Rosemont
        [45.5600, -73.5850], // Villeray
        [45.5300, -73.6200], // Outremont
        [45.4750, -73.6150], // NDG
        [45.4650, -73.6000], // Cote-des-Neiges
        [45.5800, -73.5600], // Ahuntsic
        [45.5550, -73.6350], // Saint-Laurent
        [45.5100, -73.6400], // Snowdon
    ];

    private array $streets = [
        'Rue Sainte-Catherine',
        'Boulevard Saint-Laurent',
        'Rue Sherbrooke',
        'Boulevard Rene-Levesque',
        'Rue Ontario',
        'Rue Saint-Denis',
        'Rue Jean-Talon',
        'Avenue Laurier',
    ];

    private array $boroughs = [
        'Ville-Marie',
        'Le Plateau-Mont-Royal',
        'Rosemont-La Petite-Patrie',
        'Villeray-Saint-Michel-Parc-Extension',
        'Cote-des-Neiges-Notre-Dame-de-Grace',
        'Ahuntsic-Cartierville',
    ];

    public function run(): void
    {
        $this->truncateTestData();

        $users = $this->createStaffUsers();

        $potholeCategory = ReportCategory::firstOrCreate(
            ['slug' => 'pothole'],
            [
                'label_en' => 'Pothole',
                'label_fr' => 'Nid-de-poule',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => '#d97706',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $asphaltBags = Material::updateOrCreate(
            ['sku' => 'ASP-001'],
            [
                'name' => 'Asphalt bags',
                'description' => 'Asphalt bags for pothole repairs',
                'unit' => 'bag',
                'current_stock' => 10000,
                'reserved_stock' => 0,
                'min_stock_alert' => 500,
                'avg_purchase_price' => 15.50,
                'last_purchase_price' => 15.50,
                'location' => 'Main warehouse',
                'is_active' => true,
            ]
        );

        $vendors = $this->seedVendors();

        $reports = $this->seedReports($potholeCategory->id);
        $jobs = $this->seedJobsForReports($reports, (int) $users['manager']->id, (int) $users['service_worker']->id);
        $this->seedAsphaltExpenses($jobs, $asphaltBags, $vendors, (int) $users['accountant']->id);

        $validReports = self::TOTAL_REPORTS - self::REJECTED_REPORTS;

        $this->command?->info('TestDataSeeder completed.');
        $this->command?->info('Reports: '.$reports->count()." ({$validReports} valid + ".self::REJECTED_REPORTS.' rejected)');
        $this->command?->info('Jobs: '.$jobs->count());
        $this->command?->info('Expenses: '.$jobs->count().' (asphalt bags only)');
    }

    /**
     * @return Collection<int, Vendor>
     */
    private function seedVendors(): Collection
    {
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

    private function truncateTestData(): void
    {
        DB::statement('TRUNCATE TABLE job_materials, job_workers, job_reports, expenses, repair_jobs, reports RESTART IDENTITY CASCADE');
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
        $validReports = self::TOTAL_REPORTS - self::REJECTED_REPORTS;

        $distribution = [
            'repaired' => (int) floor($validReports * 0.60),
            'scheduled' => (int) floor($validReports * 0.30),
            'received' => (int) ($validReports - floor($validReports * 0.60) - floor($validReports * 0.30)),
            'rejected' => self::REJECTED_REPORTS,
        ];

        $reports = collect();

        foreach ($distribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $createdAt = now()->subDays(rand(0, 29))->subMinutes(rand(0, 1439));
                $location = $this->randomMontrealLocation();

                $reportId = DB::table('reports')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'reporter_email' => 'citizen'.rand(1000, 999999).'@example.com',
                    'preferred_locale' => rand(0, 1) ? 'fr' : 'en',
                    'status' => $status,
                    'priority' => 'normal',
                    'category_id' => $potholeCategoryId,
                    'description' => 'Pothole reported on road surface.',
                    'address' => rand(100, 9999).' '.$this->streets[array_rand($this->streets)],
                    'neighborhood' => 'Montreal',
                    'borough' => $this->boroughs[array_rand($this->boroughs)],
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
                    'rejection_reason' => $status === 'rejected' ? 'out_of_scope' : null,
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

    /**
     * Generate a realistic location spread across Montreal.
     *
     * @return array{float, float} [lat, lng]
     */
    private function randomMontrealLocation(): array
    {
        $center = $this->montrealCenters[array_rand($this->montrealCenters)];

        // About 100m to 1400m offset around a borough center.
        $offsetLat = random_int(-120, 120) / 10000;
        $offsetLng = random_int(-170, 170) / 10000;

        $lat = $center[0] + $offsetLat;
        $lng = $center[1] + $offsetLng;

        // Keep points within a broad Montreal bounding box.
        $lat = max(45.40, min(45.70, $lat));
        $lng = max(-73.95, min(-73.45, $lng));

        return [round($lat, 6), round($lng, 6)];
    }

    /**
     * @param  Collection<int, Report>  $reports
     * @return Collection<int, RepairJob>
     */
    private function seedJobsForReports(Collection $reports, int $managerId, int $workerId): Collection
    {
        $jobs = collect();

        foreach ($reports as $report) {
            if ($report->status === 'rejected') {
                continue;
            }

            $jobStatus = match ($report->status) {
                'repaired' => 'completed',
                'scheduled' => 'planned',
                default => 'planned',
            };

            $scheduledAt = $report->first_scheduled_at ?? $report->created_at->copy()->addDays(rand(1, 5));
            $startedAt = $jobStatus === 'completed'
                ? ($report->first_started_at ?? $scheduledAt->copy()->addHours(rand(4, 24)))
                : null;
            $completedAt = $jobStatus === 'completed'
                ? ($report->completed_at ?? $startedAt?->copy()->addHours(rand(6, 48)))
                : null;

            $job = RepairJob::create([
                'title' => 'Pothole repair '.$report->uuid,
                'description' => 'Job created from report '.$report->uuid,
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

    /**
     * @param  Collection<int, RepairJob>  $jobs
     */
    private function seedAsphaltExpenses(Collection $jobs, Material $asphaltBags, Collection $vendors, int $accountantId): void
    {
        foreach ($jobs as $job) {
            $vendor = $vendors->random();
            $quantity = $job->status === 'completed' ? rand(4, 12) : rand(1, 4);
            $unitCost = 15.50;
            $subtotal = $quantity * $unitCost;
            $taxRate = 0.14975;
            $taxAmount = round($subtotal * $taxRate, 2);
            $total = round($subtotal + $taxAmount, 2);

            Expense::create([
                'repair_job_id' => $job->id,
                'material_id' => $asphaltBags->id,
                'description' => 'Asphalt bags',
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
                    'material_id' => $asphaltBags->id,
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
}
