<?php

namespace modules\newsorganizer\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Entry;
use craft\helpers\Console;
use craft\models\Section;
use craft\services\Sections;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

/**
 * News Reorganizer Console Controller
 *
 * Reorganizes news articles to proper structure levels and ordering
 *
 * @author Craft CMS
 * @since 1.0.0
 */
class ReorganizeController extends Controller
{
    /**
     * @var bool Whether to run in dry-run mode (preview only)
     */
    public bool $dryRun = false;

    /**
     * @var bool Whether to show verbose output
     */
    public bool $verbose = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'dryRun',
            'verbose'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'd' => 'dryRun',
            'v' => 'verbose'
        ]);
    }

    /**
     * Reorganizes news articles structure and ordering
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout("News Article Reorganizer\n", BaseConsole::FG_CYAN);
        $this->stdout("========================\n\n");

        if ($this->dryRun) {
            $this->stdout("Running in DRY-RUN mode - no changes will be made\n\n", BaseConsole::FG_YELLOW);
        }

        try {
            // Step 1: Get the News section
            $newsSection = $this->getNewsSection();
            if (!$newsSection) {
                $this->stderr("Error: Could not find News section\n", BaseConsole::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Step 2: Find the parent News entry
            $parentEntry = $this->getParentNewsEntry($newsSection);
            if (!$parentEntry) {
                $this->stderr("Error: Could not find parent News entry\n", BaseConsole::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout("Found parent News entry: '{$parentEntry->title}' (ID: {$parentEntry->id})\n", BaseConsole::FG_GREEN);

            // Step 3: Get all news articles at level 1 (excluding parent)
            $articlesToMove = $this->getArticlesToReorganize($newsSection, $parentEntry);
            
            if (empty($articlesToMove)) {
                $this->stdout("No articles found that need reorganizing.\n", BaseConsole::FG_YELLOW);
                return ExitCode::OK;
            }

            $this->stdout("Found " . count($articlesToMove) . " articles to reorganize\n\n");

            // Step 4: Show preview of changes
            $this->showPreview($articlesToMove, $parentEntry);

            if ($this->dryRun) {
                $this->stdout("\nDry-run complete. Use without --dry-run to execute changes.\n", BaseConsole::FG_YELLOW);
                return ExitCode::OK;
            }

            // Step 5: Confirm execution
            if (!$this->confirm("Proceed with reorganizing " . count($articlesToMove) . " articles?")) {
                $this->stdout("Operation cancelled.\n", BaseConsole::FG_YELLOW);
                return ExitCode::OK;
            }

            // Step 6: Execute the reorganization
            $this->executeReorganization($articlesToMove, $parentEntry);

            $this->stdout("\nReorganization completed successfully!\n", BaseConsole::FG_GREEN);
            $this->stdout("All articles have been moved to level 2 and ordered by publication date (newest first).\n");

        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n", BaseConsole::FG_RED);
            if ($this->verbose) {
                $this->stderr($e->getTraceAsString() . "\n");
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Get the News section
     */
    private function getNewsSection(): ?Section
    {
        $sectionsService = Craft::$app->get('sections');
        return $sectionsService->getSectionByHandle('newsSection');
    }

    /**
     * Get the parent News entry at level 1
     */
    private function getParentNewsEntry(Section $section): ?Entry
    {
        return Entry::find()
            ->sectionId($section->id)
            ->typeId($this->getNewsPageTypeId())
            ->level(1)
            ->one();
    }

    /**
     * Get the News Page entry type ID
     */
    private function getNewsPageTypeId(): ?int
    {
        $newsSection = $this->getNewsSection();
        if (!$newsSection) {
            return null;
        }

        foreach ($newsSection->getEntryTypes() as $entryType) {
            if ($entryType->handle === 'newsPage') {
                return $entryType->id;
            }
        }

        return null;
    }

    /**
     * Get all news articles that need to be reorganized
     */
    private function getArticlesToReorganize(Section $section, Entry $parentEntry): array
    {
        // Get all entries at level 1 that are NOT the parent entry
        $articles = Entry::find()
            ->sectionId($section->id)
            ->level(1)
            ->where(['not', ['elements.id' => $parentEntry->id]])
            ->orderBy(['postDate' => SORT_DESC]) // Order by newest first
            ->all();

        return $articles;
    }

    /**
     * Show preview of what will be changed
     */
    private function showPreview(array $articles, Entry $parentEntry): void
    {
        $this->stdout("Preview of changes:\n", BaseConsole::FG_CYAN);
        $this->stdout("==================\n\n");

        $this->stdout("Parent entry: {$parentEntry->title} (Level 1)\n");
        $this->stdout("Articles to move under parent (Level 1 → Level 2):\n\n");

        foreach ($articles as $index => $article) {
            $postDate = $article->postDate ? $article->postDate->format('Y-m-d H:i:s') : 'No date';
            $this->stdout(sprintf(
                "%2d. %s (ID: %d) - %s\n",
                $index + 1,
                $article->title,
                $article->id,
                $postDate
            ));
        }

        $this->stdout("\nNew structure will be:\n");
        $this->stdout("├── {$parentEntry->title} (Level 1)\n");
        foreach ($articles as $index => $article) {
            $prefix = $index === count($articles) - 1 ? '└──' : '├──';
            $this->stdout("    {$prefix} {$article->title} (Level 2)\n");
        }
    }

    /**
     * Execute the reorganization
     */
    private function executeReorganization(array $articles, Entry $parentEntry): void
    {
        $this->stdout("\nExecuting reorganization...\n", BaseConsole::FG_CYAN);

        $structuresService = Craft::$app->getStructures();
        $elementsService = Craft::$app->getElements();
        
        $total = count($articles);
        $processed = 0;

        foreach ($articles as $article) {
            $processed++;
            
            if ($this->verbose) {
                $this->stdout("Processing article {$processed}/{$total}: {$article->title}\n");
            } else {
                Console::updateProgress($processed, $total, "Processing articles");
            }

            try {
                // Move the article to be a child of the parent entry
                $structuresService->moveElement(
                    $article,
                    $parentEntry,
                    'append'
                );

                // Save the element to ensure all changes are persisted
                $elementsService->saveElement($article);

            } catch (\Exception $e) {
                $this->stderr("\nError processing article '{$article->title}': " . $e->getMessage() . "\n", BaseConsole::FG_RED);
                continue;
            }
        }

        if (!$this->verbose) {
            Console::endProgress();
        }

        // Clear caches
        $this->stdout("\nClearing caches...\n");
        Craft::$app->getTemplateCaches()->deleteAllCaches();
        
        // Update search indexes
        $this->stdout("Updating search indexes...\n");
        Craft::$app->getSearch()->indexElementAttributes($parentEntry);
        
        foreach ($articles as $article) {
            Craft::$app->getSearch()->indexElementAttributes($article);
        }
    }
}
