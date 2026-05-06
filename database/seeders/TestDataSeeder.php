<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Material;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates realistic demo data for testing the admin dashboard.
 *
 * Run this AFTER the base seeders (RoleSeeder, ReportCategorySeeder, etc.):
 *   php artisan db:seed --class=TestDataSeeder
 *
 * Creates:
 *   - 5 staff users (one per role)
 *   - 80 reports across all statuses with realistic Montreal locations
 *   - 15 repair jobs linked to reports
 *   - 40 expenses across categories
 *   - 10 materials in inventory
 */
class TestDataSeeder extends Seeder
{
    /** @var array<array{float, float}> Realistic Montreal Island coordinates [lat, lng] */
    private array $montrealLocations = [
        [45.5017, -73.5673], // Downtown
        [45.5088, -73.5540], // Old Montreal
        [45.5242, -73.5810], // Plateau-Mont-Royal
        [45.5390, -73.5950], // Mile End
        [45.5510, -73.6100], // Rosemont
        [45.5600, -73.5850], // Villeray
        [45.5300, -73.6200], // Outremont
        [45.4750, -73.6150], // Notre-Dame-de-Grace
        [45.4650, -73.6000], // Cote-des-Neiges
        [45.4800, -73.5700], // Westmount
        [45.5200, -73.5500], // Village
        [45.5400, -73.5400], // Gay Village
        [45.4950, -73.6300], // Hampstead
        [45.4700, -73.6400], // Cote-Saint-Luc
        [45.5800, -73.5600], // Ahuntsic
        [45.5900, -73.5400], // Cartierville
        [45.5100, -73.6400], // Montreal West
        [45.5250, -73.6600], // Snowdon
        [45.5450, -73.6200], // Parc-Extension
        [45.5550, -73.6350], // Ville Saint-Laurent
    ];

    public function run(): void
    {
        $this->command->info('Creating test staff users...');
        $users = $this->createStaffUsers();

        $this->command->info('Creating test materials...');
        $this->createMaterials();

        $this->command->info('Creating test reports...');
        $reports = $this->createReports();

        $this->command->info('Creating test repair jobs...');
        $repairJobs = $this->createRepairJobs($users, $reports);

        $this->command->info('Creating test expenses...');
        $this->createExpenses($repairJobs, $users);

        $this->command->info('Test data seeded successfully!');
        $this->command->info('');
        $this->command->info('Admin login: admin@nidvite.ca / changeme-strong-password-2026');
        $this->command->info("Reports: {$reports->count()}");
        $this->command->info("Repair Jobs: {$repairJobs->count()}");
    }

    /**
     * Create one user per role for testing RBAC.
     *
     * @return array<string, User>
     */
    private function createStaffUsers(): array
    {
        $roles = Role::all()->keyBy('slug');
        $users = [];

        $staff = [
            'manager' => ['name' => 'Marie Gestionnaire', 'email' => 'manager@nidvite.test'],
            'service_worker' => ['name' => 'Jean Travailleur', 'email' => 'worker@nidvite.test'],
            'accountant' => ['name' => 'Pierre Comptable', 'email' => 'accountant@nidvite.test'],
            'viewer' => ['name' => 'Sophie Lectrice', 'email' => 'viewer@nidvite.test'],
        ];

        foreach ($staff as $slug => $data) {
            $users[$slug] = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'role_id' => $roles[$slug]->id,
                    'locale' => 'fr',
                    'is_active' => true,
                ]
            );
        }

        return $users;
    }

    private function createMaterials(): void
    {
        $materials = [
            ['sku' => 'ASP-001', 'name' => 'Asphalt chaud (tonne)', 'unit' => 'tonne', 'current_stock' => 50, 'min_stock_alert' => 10, 'avg_purchase_price' => 120.00],
            ['sku' => 'CON-001', 'name' => 'Ciment Portland (sac)', 'unit' => 'sac', 'current_stock' => 200, 'min_stock_alert' => 50, 'avg_purchase_price' => 15.50],
            ['sku' => 'AGG-001', 'name' => 'Granulat 0-20mm (tonne)', 'unit' => 'tonne', 'current_stock' => 100, 'min_stock_alert' => 20, 'avg_purchase_price' => 35.00],
            ['sku' => 'SEA-001', 'name' => 'Scellant bitumineux (seau)', 'unit' => 'seau', 'current_stock' => 30, 'min_stock_alert' => 5, 'avg_purchase_price' => 85.00],
            ['sku' => 'BAR-001', 'name' => 'Barrières de chantier', 'unit' => 'unité', 'current_stock' => 150, 'min_stock_alert' => 30, 'avg_purchase_price' => 45.00],
            ['sku' => 'CÔN-001', 'name' => 'Cônes de signalisation', 'unit' => 'unité', 'current_stock' => 300, 'min_stock_alert' => 50, 'avg_purchase_price' => 12.00],
            ['sku' => 'PEI-001', 'name' => 'Peinture routine blanche (seau)', 'unit' => 'seau', 'current_stock' => 25, 'min_stock_alert' => 5, 'avg_purchase_price' => 95.00],
            ['sku' => 'TUB-001', 'name' => 'Tuyau PVC 200mm (mètre)', 'unit' => 'mètre', 'current_stock' => 500, 'min_stock_alert' => 100, 'avg_purchase_price' => 18.50],
            ['sku' => 'SAB-001', 'name' => 'Sable de jointoiement (tonne)', 'unit' => 'tonne', 'current_stock' => 40, 'min_stock_alert' => 10, 'avg_purchase_price' => 55.00],
            ['sku' => 'GÉO-001', 'name' => 'Géotextile (rouleau)', 'unit' => 'rouleau', 'current_stock' => 15, 'min_stock_alert' => 3, 'avg_purchase_price' => 220.00],
        ];

        foreach ($materials as $data) {
            Material::firstOrCreate(
                ['sku' => $data['sku']],
                array_merge($data, [
                    'description' => "Matériel pour travaux routiers - {$data['name']}",
                    'reserved_stock' => 0,
                    'last_purchase_price' => $data['avg_purchase_price'],
                    'location' => 'Entrepôt principal',
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * @return Collection<int, Report>
     */
    private function createReports(): Collection
    {
        $categories = ReportCategory::all();
        $statuses = [
            'received' => 25,
            'verified' => 15,
            'scheduled' => 12,
            'in_progress' => 10,
            'repaired' => 15,
            'rejected' => 3,
        ];

        $reports = collect();
        $now = now();

        foreach ($statuses as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $location = $this->montrealLocations[array_rand($this->montrealLocations)];
                $createdAt = $now->copy()->subDays(rand(0, 60))->subHours(rand(0, 23));

                $report = Report::create([
                    'reporter_email' => $this->generateMontrealEmail(),
                    'preferred_locale' => rand(0, 10) > 3 ? 'fr' : 'en',
                    'status' => $status,
                    'priority' => $this->randomPriority(),
                    'category_id' => $categories->random()->id,
                    'description' => $this->generateReportDescription(),
                    'address' => $this->generateMontrealAddress(),
                    'neighborhood' => $this->generateNeighborhood($location),
                    'borough' => $this->generateBorough($location),
                    'geofence_passed' => true,
                    'is_spam' => false,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Set PostGIS location
                DB::statement(
                    'UPDATE reports SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
                    [$location[1], $location[0], $report->id]
                );

                // Set status-specific timestamps
                $this->setStatusTimestamps($report, $status, $createdAt);

                $reports->push($report);
            }
        }

        return $reports;
    }

    /**
     * @param  array<string, User>  $users
     * @param  Collection<int, Report>  $reports
     * @return Collection<int, RepairJob>
     */
    private function createRepairJobs(array $users, Collection $reports): Collection
    {
        $repairReports = $reports->whereIn('status', ['scheduled', 'in_progress', 'repaired']);
        $jobs = collect();

        $jobTitles = [
            'Réparation nid-de-poule - Rue Sainte-Catherine',
            'Refonte trottoir - Boulevard Saint-Laurent',
            'Remplacement lampadaire - Avenue du Parc',
            'Réparation chaussée - Rue Sherbrooke',
            'Travaux d\'asphaltage - Boulevard René-Lévesque',
            'Réparation trottoir - Rue Ontario',
            'Remplacement regard - Avenue Papineau',
            'Réparation surface - Boulevard Décarie',
            'Travaux de voirie - Rue Saint-Denis',
            'Réparation d\'urgence - Chemin de la Côte-des-Neiges',
            'Refonte intersection - Rue Saint-Hubert',
            'Remplacement conduite - Avenue Van Horne',
            'Réparation chaussée - Boulevard Côte-Vertu',
            'Travaux trottoir - Rue Jean-Talon',
            'Réparation nid-de-poule - Avenue Laurier',
        ];

        $managerId = $users['manager']->id ?? null;
        $workerId = $users['service_worker']->id ?? null;

        foreach ($repairReports->take(15) as $index => $report) {
            $scheduledAt = $report->first_scheduled_at ?? $report->created_at->copy()->addDays(rand(1, 7));
            $status = $report->status === 'repaired' ? 'completed' : ($report->status === 'in_progress' ? 'in_progress' : 'planned');
            $startedAt = in_array($status, ['in_progress', 'completed'], true)
                ? $scheduledAt->copy()->addDays(rand(0, 2))
                : null;
            $completedAt = $status === 'completed'
                ? $startedAt?->copy()->addDays(rand(1, 5))
                : null;

            $estimatedCost = rand(500, 5000) + (rand(0, 99) / 100);
            $actualCost = $status === 'completed'
                ? $estimatedCost * (rand(80, 130) / 100)
                : null;

            $job = RepairJob::create([
                'title' => $jobTitles[$index] ?? "Travaux - {$report->address}",
                'description' => "Intervention sur signalement #{$report->uuid}. {$report->description}",
                'scheduled_at' => $scheduledAt,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'status' => $status,
                'created_by' => $managerId,
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $actualCost,
            ]);

            // Link report to job
            DB::table('job_reports')->insert([
                'repair_job_id' => $job->id,
                'report_id' => $report->id,
                'cost_allocation_percentage' => 100,
                'created_at' => $scheduledAt,
                'updated_at' => $scheduledAt,
            ]);

            // Assign worker
            if ($workerId !== null) {
                DB::table('job_workers')->insert([
                    'repair_job_id' => $job->id,
                    'user_id' => $workerId,
                    'role_in_job' => 'lead',
                    'hours_worked' => $status === 'completed' ? rand(4, 40) : rand(1, 20),
                    'created_at' => $startedAt ?? $scheduledAt,
                    'updated_at' => $startedAt ?? $scheduledAt,
                ]);
            }

            $jobs->push($job);
        }

        return $jobs;
    }

    /**
     * @param  Collection<int, RepairJob>  $repairJobs
     * @param  array<string, User>  $users
     */
    private function createExpenses(Collection $repairJobs, array $users): void
    {
        $expenseCategories = ExpenseCategory::all();
        $materials = Material::all();
        $accountantId = $users['accountant']->id ?? null;

        $vendors = [
            'Béton Provincial', 'Ciment Québec', 'Matériaux Rive-Sud',
            'Quincaillerie Monkland', 'Fournitures JTM', 'Location d\'équipement Laval',
            'Pétrole Plus', 'Transport Martin', 'Quincaillerie Centre-ville',
            'Fournitures Industrielles MTL',
        ];

        // Create expenses for completed jobs
        foreach ($repairJobs->where('status', 'completed') as $job) {
            $expenseCount = rand(2, 5);
            $jobTotal = 0;

            for ($i = 0; $i < $expenseCount; $i++) {
                $category = $expenseCategories->random();
                $quantity = rand(1, 20);
                $unitCost = rand(50, 500) + (rand(0, 99) / 100);
                $subtotal = $quantity * $unitCost;
                $taxRate = 0.14975;
                $taxAmount = $subtotal * $taxRate;
                $total = $subtotal + $taxAmount;
                $jobTotal += $total;

                $materialId = $category->slug === 'materials' && $materials->isNotEmpty()
                    ? $materials->random()->id
                    : null;

                $incurredAt = $job->started_at?->copy()->addDays(rand(0, 3)) ?? now()->subDays(rand(1, 30));

                Expense::create([
                    'repair_job_id' => $job->id,
                    'category_id' => $category->id,
                    'material_id' => $materialId,
                    'description' => $this->generateExpenseDescription($category->slug),
                    'quantity' => $quantity,
                    'unit' => $this->getUnitForCategory($category->slug),
                    'unit_cost' => $unitCost,
                    'subtotal' => round($subtotal, 2),
                    'tax_rate' => $taxRate,
                    'tax_amount' => round($taxAmount, 2),
                    'total' => round($total, 2),
                    'vendor' => $vendors[array_rand($vendors)],
                    'incurred_at' => $incurredAt,
                    'created_by' => $accountantId,
                ]);
            }

            // Update job actual cost to match expenses
            $job->update(['actual_cost' => round($jobTotal, 2)]);
        }

        // Note: expenses table requires repair_job_id (NOT NULL), so all expenses are job-linked
    }

    private function generateMontrealEmail(): string
    {
        $domains = ['gmail.com', 'hotmail.com', 'yahoo.ca', 'videotron.ca', 'bell.net', 'umontreal.ca', 'concordia.ca'];
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Michel', 'Isabelle', 'Francois', 'Catherine', 'Andre', 'Nathalie'];
        $lastNames = ['Tremblay', 'Gagnon', 'Roy', 'Cote', 'Bouchard', 'Gauthier', 'Morin', 'Lavoie', 'Fortin', 'Bergeron'];

        return strtolower($firstNames[array_rand($firstNames)].'.'.$lastNames[array_rand($lastNames)].rand(1, 999)).'@'.$domains[array_rand($domains)];
    }

    private function randomPriority(): string
    {
        $weights = ['low' => 30, 'normal' => 50, 'high' => 15, 'critical' => 5];
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $priority => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $priority;
            }
        }

        return 'normal';
    }

    private function generateReportDescription(): string
    {
        $descriptions = [
            'Grand nid-de-poule sur la chaussée, dangereux pour les cyclistes.',
            'Trottoir très endommagé avec des fissures profondes.',
            'Lampadaire éteint depuis plusieurs jours, rue mal éclairée.',
            'Nid-de-poule près d\'un arrêt d\'autobus, risque d\'accident.',
            'Peinture de ligne effacée, confusion pour les automobilistes.',
            'Égout bouché qui déborde lors des pluies.',
            'Fissure importante dans l\'asphalte qui s\'agrandit.',
            'Bordure de trottoir brisée, accès difficile pour fauteuils roulants.',
            'Signalisation routière inclinée après la tempête.',
            'Affaissement de la chaussée près d\'une entrée de garage.',
            'Graffiti sur mur public à retirer.',
            'Débris de verre sur la piste cyclable.',
            'Trou dans l\'asphalte de plus de 1 mètre de diamètre.',
            'Panneau de signalisation arraché par le vent.',
            'Accumulation d\'eau stagnante après chaque pluie.',
        ];

        return $descriptions[array_rand($descriptions)];
    }

    private function generateMontrealAddress(): string
    {
        $streetNumbers = range(100, 9999, rand(50, 200));
        $streets = [
            'Rue Sainte-Catherine', 'Boulevard Saint-Laurent', 'Avenue du Parc',
            'Rue Sherbrooke', 'Boulevard René-Lévesque', 'Rue Ontario',
            'Avenue Papineau', 'Boulevard Décarie', 'Rue Saint-Denis',
            'Chemin de la Côte-des-Neiges', 'Rue Saint-Hubert', 'Avenue Van Horne',
            'Boulevard Côte-Vertu', 'Rue Jean-Talon', 'Avenue Laurier',
            'Boulevard Saint-Joseph', 'Rue Rachel', 'Avenue Mont-Royal',
            'Rue Masson', 'Boulevard Crémazie',
        ];

        return $streetNumbers[array_rand($streetNumbers)].' '.$streets[array_rand($streets)];
    }

    /**
     * @param  array{float, float}  $location
     */
    private function generateNeighborhood(array $location): string
    {
        $neighborhoods = [
            'Downtown' => [[45.49, 45.52], [-73.59, -73.55]],
            'Plateau-Mont-Royal' => [[45.51, 45.54], [-73.60, -73.56]],
            'Rosemont' => [[45.54, 45.57], [-73.62, -73.58]],
            'Villeray' => [[45.55, 45.58], [-73.62, -73.58]],
            'NDG' => [[45.46, 45.49], [-73.64, -73.60]],
            'Côte-des-Neiges' => [[45.47, 45.50], [-73.63, -73.59]],
            'Westmount' => [[45.47, 45.49], [-73.60, -73.57]],
            'Ahuntsic' => [[45.57, 45.60], [-73.68, -73.63]],
            'Ville Saint-Laurent' => [[45.50, 45.53], [-73.70, -73.66]],
            'Hochelaga' => [[45.53, 45.56], [-73.56, -73.53]],
        ];

        foreach ($neighborhoods as $name => $bounds) {
            if ($location[0] >= $bounds[0][0] && $location[0] <= $bounds[0][1]
                && $location[1] >= $bounds[1][0] && $location[1] <= $bounds[1][1]) {
                return $name;
            }
        }

        return 'Montreal';
    }

    /**
     * @param  array{float, float}  $location
     */
    private function generateBorough(array $location): string
    {
        $boroughs = [
            'Ville-Marie' => [[45.49, 45.52], [-73.57, -73.54]],
            'Le Plateau-Mont-Royal' => [[45.51, 45.54], [-73.60, -73.56]],
            'Rosemont–La Petite-Patrie' => [[45.53, 45.56], [-73.62, -73.58]],
            'Côte-des-Neiges–Notre-Dame-de-Grâce' => [[45.46, 45.50], [-73.65, -73.59]],
            'Villeray–Saint-Michel–Parc-Extension' => [[45.53, 45.57], [-73.65, -73.60]],
            'Ahuntsic-Cartierville' => [[45.55, 45.60], [-73.70, -73.63]],
            'Saint-Laurent' => [[45.49, 45.52], [-73.72, -73.66]],
        ];

        foreach ($boroughs as $name => $bounds) {
            if ($location[0] >= $bounds[0][0] && $location[0] <= $bounds[0][1]
                && $location[1] >= $bounds[1][0] && $location[1] <= $bounds[1][1]) {
                return $name;
            }
        }

        return 'Montreal';
    }

    private function setStatusTimestamps(Report $report, string $status, Carbon $createdAt): void
    {
        $updates = [];

        switch ($status) {
            case 'verified':
                $updates['first_scheduled_at'] = $createdAt->copy()->addHours(rand(1, 24));
                break;
            case 'scheduled':
                $updates['first_scheduled_at'] = $createdAt->copy()->addHours(rand(1, 48));
                break;
            case 'in_progress':
                $updates['first_scheduled_at'] = $createdAt->copy()->addHours(rand(1, 48));
                $updates['first_started_at'] = $createdAt->copy()->addDays(rand(1, 5));
                break;
            case 'repaired':
                $updates['first_scheduled_at'] = $createdAt->copy()->addHours(rand(1, 48));
                $updates['first_started_at'] = $createdAt->copy()->addDays(rand(1, 5));
                $updates['completed_at'] = $createdAt->copy()->addDays(rand(5, 20));
                break;
            case 'rejected':
                $updates['rejection_reason'] = ['false_report', 'out_of_scope', 'duplicate', 'insufficient_info'][array_rand(['false_report', 'out_of_scope', 'duplicate', 'insufficient_info'])];
                break;
        }

        if (! empty($updates)) {
            $report->update($updates);
        }
    }

    private function generateExpenseDescription(string $categorySlug): string
    {
        $descriptions = [
            'materials' => ['Achat d\'asphalte chaud', 'Ciment Portland', 'Granulat pour base', 'Scellant bitumineux', 'Sable de jointoiement'],
            'labor' => ['Heures de main-d\'œuvre - Équipe A', 'Heures supplémentaires weekend', 'Technicien spécialisé', 'Surveillance chantier'],
            'fuel' => ['Diesel pour camion benne', 'Essence pour équipement', 'Carburant chauffage asphalt'],
            'equipment_rental' => ['Location compacteur vibrant', 'Location camion-citerne', 'Location plateforme élévatrice'],
            'transport' => ['Transport matériaux', 'Livraison urgent', 'Déplacement équipe'],
            'other' => ['Repas d\'équipe', 'Frais de stationnement', 'Petits outillages'],
        ];

        $items = $descriptions[$categorySlug] ?? $descriptions['other'];

        return $items[array_rand($items)];
    }

    private function getUnitForCategory(string $categorySlug): string
    {
        return match ($categorySlug) {
            'materials' => ['tonne', 'sac', 'seau', 'mètre'][array_rand(['tonne', 'sac', 'seau', 'mètre'])],
            'labor' => 'heure',
            'fuel' => 'litre',
            'equipment_rental' => 'jour',
            'transport' => 'km',
            default => 'unité',
        };
    }
}
