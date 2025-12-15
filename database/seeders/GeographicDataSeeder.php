<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{County, Constituency, Ward};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class GeographicDataSeeder extends Seeder
{
    private const GITHUB_URL = 'https://raw.githubusercontent.com/stevehoober254/kenya-county-data/refs/heads/main/county_data.json';
    private const TARGET_COUNTIES = ['Siaya', 'Nakuru'];
    
    public function run(): void
    {
        $this->command->warn('🚀 Starting geographic data seeding from GitHub...');
        
        DB::beginTransaction();
        
        try {
            $this->clearExistingData();
            
            $data = $this->fetchFromGitHub();
            $this->seedData($data);
            
            DB::commit();
            
            $this->displaySummary();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Fetch JSON data from GitHub
     */
    private function fetchFromGitHub(): array
    {
        $this->command->info('📡 Fetching data from GitHub...');
        
        try {
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
            ])
            ->timeout(60) // Increase timeout for large file
            ->retry(3, 1000) // Retry 3 times with 1 second delay
            ->get(self::GITHUB_URL);
            
            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}: Failed to fetch data");
            }
            
            $data = $response->json();
            
            if (!is_array($data)) {
                throw new \Exception("Invalid JSON format");
            }
            
            $this->command->info("   ✓ Fetched data for " . count($data) . " counties");
            
            return $data;
            
        } catch (\Exception $e) {
            $this->command->error("   ✗ Error fetching data: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Clear existing geographic data
     */
    private function clearExistingData(): void
    {
        $this->command->info('🗑️  Clearing existing data...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Ward::truncate();
        Constituency::truncate();
        County::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('   ✓ Cleared existing data');
    }
    
    /**
     * Seed counties, constituencies, and wards from nested data
     */
    private function seedData(array $data): void
    {
        $countyCount = 0;
        $constituencyCount = 0;
        $wardCount = 0;
        
        foreach ($data as $countyData) {
            $countyName = $countyData['name'] ?? null;
            
            if (!$countyName) {
                continue;
            }
            
            // Only process Siaya and Nakuru counties
            if (!in_array($countyName, self::TARGET_COUNTIES)) {
                continue;
            }
            
            $this->command->info("🏛️  Processing {$countyName}...");
            
            // Create county
            $county = County::create([
                'name' => $countyName,
                'code' => $this->generateCountyCode($countyName),
            ]);
            
            $countyCount++;
            $this->command->info("   ✓ Created county: {$countyName}");
            
            // Process constituencies
            $constituencies = $countyData['constituencies'] ?? [];
            
            foreach ($constituencies as $constituencyData) {
                $constituencyName = $constituencyData['name'] ?? null;
                
                if (!$constituencyName) {
                    continue;
                }
                
                // Create constituency
                $constituency = Constituency::create([
                    'county_id' => $county->id,
                    'name' => $constituencyName,
                    'code' => $this->generateConstituencyCode($county->code, $constituencyCount),
                ]);
                
                $constituencyCount++;
                $this->command->info("      ✓ Constituency: {$constituencyName}");
                
                // Process wards
                $wards = $constituencyData['wards'] ?? [];
                
                foreach ($wards as $wardData) {
                    $wardName = $wardData['name'] ?? null;
                    
                    if (!$wardName) {
                        continue;
                    }
                    
                    // Create ward
                    Ward::create([
                        'constituency_id' => $constituency->id,
                        'name' => $wardName,
                        'code' => $this->generateWardCode($constituency->code, $wardCount),
                    ]);
                    
                    $wardCount++;
                }
                
                $this->command->info("         └─ Seeded " . count($wards) . " wards");
            }
        }
        
        $this->command->newLine();
        $this->command->info("📊 Processed:");
        $this->command->info("   Counties: {$countyCount}");
        $this->command->info("   Constituencies: {$constituencyCount}");
        $this->command->info("   Wards: {$wardCount}");
    }
    
    /**
     * Generate county code
     */
    private function generateCountyCode(string $countyName): string
    {
        $codes = [
            'Siaya' => '041',
            'Nakuru' => '032',
        ];
        
        return $codes[$countyName] ?? str_pad((string)rand(1, 47), 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate constituency code
     */
    private function generateConstituencyCode(string $countyCode, int $index): string
    {
        return $countyCode . '-' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate ward code
     */
    private function generateWardCode(string $constituencyCode, int $index): string
    {
        return $constituencyCode . '-' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Display seeding summary
     */
    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('========================================');
        $this->command->info('       📊 SEEDING SUMMARY');
        $this->command->info('========================================');
        $this->command->newLine();
        
        foreach (County::withCount(['constituencies', 'wards'])->get() as $county) {
            $this->command->info("📍 {$county->name} (Code: {$county->code})");
            $this->command->info("   ├─ Constituencies: {$county->constituencies_count}");
            $this->command->info("   └─ Wards: {$county->wards_count}");
            
            // Show constituencies
            foreach ($county->constituencies()->withCount('wards')->get() as $constituency) {
                $this->command->info("      ├─ {$constituency->name} ({$constituency->wards_count} wards)");
            }
            
            $this->command->newLine();
        }
        
        $this->command->info('========================================');
        $this->command->info('✅ Total Counties: ' . County::count());
        $this->command->info('✅ Total Constituencies: ' . Constituency::count());
        $this->command->info('✅ Total Wards: ' . Ward::count());
        $this->command->info('========================================');
        $this->command->newLine();
        $this->command->info('🎉 Seeding completed successfully!');
    }
}