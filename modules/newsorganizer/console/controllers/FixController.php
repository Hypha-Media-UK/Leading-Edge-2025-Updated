<?php

namespace modules\newsorganizer\console\controllers;

use craft\console\Controller;
use craft\elements\Entry;
use craft\helpers\Console;

class FixController extends Controller
{
    public function actionStructure()
    {
        $this->stdout("CraftCMS News Articles Structure Fix\n", Console::FG_GREEN);
        $this->stdout("=====================================\n\n");

        // Get the News section
        $sectionsService = \Craft::$app->sections;
        $newsSection = $sectionsService->getSectionByHandle('newsSection');
        if (!$newsSection) {
            $this->stderr("Error: Could not find newsSection\n", Console::FG_RED);
            return 1;
        }

        // Get the structure
        $structuresService = \Craft::$app->structures;
        $structure = $structuresService->getStructureById($newsSection->structureId);
        if (!$structure) {
            $this->stderr("Error: Could not find structure\n", Console::FG_RED);
            return 1;
        }

        $this->stdout("Found News section (ID: {$newsSection->id}) with structure (ID: {$structure->id})\n");

        // Find the parent News entry (newsPage entry type at level 1)
        $parentEntry = Entry::find()
            ->section('newsSection')
            ->type('newsPage')
            ->level(1)
            ->one();

        if (!$parentEntry) {
            $this->stderr("Error: Could not find parent News entry\n", Console::FG_RED);
            return 1;
        }

        $this->stdout("Found parent News entry: {$parentEntry->title} (ID: {$parentEntry->id})\n");

        // Find all news articles at level 1 (these need to be moved)
        $articlesToMove = Entry::find()
            ->section('newsSection')
            ->type('newsArticle')
            ->level(1)
            ->orderBy('dateCreated ASC') // Get oldest first so we can reverse the order
            ->all();

        $this->stdout("Found " . count($articlesToMove) . " articles to move\n\n");

        if (empty($articlesToMove)) {
            $this->stdout("No articles to move. Exiting.\n", Console::FG_YELLOW);
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm("Do you want to proceed with moving these articles?")) {
            $this->stdout("Operation cancelled.\n", Console::FG_YELLOW);
            return 0;
        }

        // Start transaction
        $transaction = \Craft::$app->db->beginTransaction();

        try {
            // Move each article to be a child of the parent entry
            // Process in reverse order so newest articles end up first
            $articlesToMove = array_reverse($articlesToMove);
            
            foreach ($articlesToMove as $index => $article) {
                $this->stdout("Moving article: {$article->title} (ID: {$article->id})\n");
                
                // Move the article to be a child of the parent
                $success = $structuresService->moveAfter(
                    $structure->id,
                    $article,
                    $parentEntry,
                    'child'
                );
                
                if (!$success) {
                    throw new \Exception("Failed to move article: {$article->title}");
                }
            }
            
            // Commit the transaction
            $transaction->commit();
            $this->stdout("\nSuccess! Moved " . count($articlesToMove) . " articles to level 2 under the News parent.\n", Console::FG_GREEN);
            $this->stdout("Articles are now ordered with newest first.\n", Console::FG_GREEN);
            
        } catch (\Exception $e) {
            // Rollback on error
            $transaction->rollBack();
            $this->stderr("Error: " . $e->getMessage() . "\n", Console::FG_RED);
            $this->stderr("All changes have been rolled back.\n", Console::FG_RED);
            return 1;
        }

        // Verify the results
        $level1Articles = Entry::find()
            ->section('newsSection')
            ->type('newsArticle')
            ->level(1)
            ->count();

        $level2Articles = Entry::find()
            ->section('newsSection')
            ->type('newsArticle')
            ->level(2)
            ->count();

        $this->stdout("\nFinal verification:\n", Console::FG_CYAN);
        $this->stdout("Level 1 articles: {$level1Articles}\n");
        $this->stdout("Level 2 articles: {$level2Articles}\n");

        // Show first 5 articles to verify order
        $firstFive = Entry::find()
            ->section('newsSection')
            ->type('newsArticle')
            ->level(2)
            ->orderBy('lft ASC')
            ->limit(5)
            ->all();

        $this->stdout("\nFirst 5 articles (should be newest first):\n", Console::FG_CYAN);
        foreach ($firstFive as $article) {
            $this->stdout("- {$article->title} (Created: {$article->dateCreated->format('Y-m-d H:i:s')})\n");
        }

        $this->stdout("\nDone! Please check your Craft admin panel to verify the changes.\n", Console::FG_GREEN);
        return 0;
    }
}
