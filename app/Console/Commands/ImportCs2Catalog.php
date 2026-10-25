<?php

namespace App\Console\Commands;

use App\Models\DataSource;
use App\Services\Catalog\Cs2GameFileImporter;
use App\Support\CatalogCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class ImportCs2Catalog extends Command
{
    protected $signature = 'catalog:import-cs2 {--keep-demo : Leave the seeded demo catalog published}';

    protected $description = 'Import CS2 item definitions from the game files distributed through Steam';

    public function handle(Cs2GameFileImporter $importer): int
    {
        ini_set('memory_limit', '1024M');
        $source = DataSource::query()->updateOrCreate(
            ['slug' => 'cs2-game-files'],
            [
                'name' => 'CS2 game files',
                'driver' => 'cs2-game-files',
                'is_enabled' => true,
                'notes' => 'Item names, rarities, float ranges, wears, collections, and crate contents parsed from the CS2 files Steam distributes. Images use the URL in that data, which may be a Steam CDN link or a mirror of the same inventory image. This source does not include Steam Market prices or drop rates.',
            ],
        );

        try {
            if (! $this->option('keep-demo')) {
                $importer->archiveDemoCatalog();
                $this->info('Archived the seeded demo catalog.');
            }

            $collections = $this->download('collections.json');
            $this->info('Collections: '.$importer->importCollections($collections));

            $this->info('Skins, knives, and gloves: '.$importer->importSkins($this->download('skins.json')));
            $this->info('Stickers: '.$importer->importTyped($this->download('stickers.json'), 'sticker'));
            $this->info('Agents: '.$importer->importTyped($this->download('agents.json'), 'agent'));

            $crates = $this->download('crates.json');
            $this->info('Cases and capsules: '.$importer->importCrates($crates));
            $importer->linkCollectionCrates($collections);
            $this->info('Cases linked to a key: '.$importer->applyKeys($this->download('keys.json')));
            $importer->featureRealRecords();
            CatalogCache::bump();

            $source->update([
                'last_synced_at' => now(),
                'last_status' => 'ok',
                'last_error' => null,
            ]);
            $this->info('Import finished. Steam Market prices were not requested.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $source->update([
                'last_synced_at' => now(),
                'last_status' => 'error',
                'last_error' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function download(string $file): array
    {
        $this->line('Downloading '.$file.'...');
        $url = rtrim((string) config('clutchbase.cs2_catalog_url'), '/').'/'.$file;
        $response = Http::timeout(180)
            ->withHeaders(['User-Agent' => 'ClutchBase/1.0'])
            ->get($url);
        $response->throw();
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException($file.' was not a JSON list.');
        }

        return $payload;
    }
}
